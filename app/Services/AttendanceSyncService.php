<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSettings;
use App\Models\Employee;
use App\Models\RawDeviceData;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AttendanceSyncService
{
    /**
     * @return array{date:string,created:int,updated:int,unchanged:int,total_groups:int}
     */
    public function syncForDate(Carbon $selectedDate): array
    {
        $dateString = $selectedDate->toDateString();

        // Raw punch timestamps are stored as-is with no timezone conversion (see
        // resolvePunchDateTime()/resolveScheduleThresholds()), so the grouping
        // window must be plain calendar-day boundaries for that same date string
        // rather than a timezone-shifted range - otherwise evening punches get
        // bucketed into the wrong day and compared against the wrong threshold.
        $rangeStart = $dateString.' 00:00:00';
        $rangeEnd = $dateString.' 23:59:59';

        $rawGroups = RawDeviceData::query()
            ->where(function ($query) use ($rangeStart, $rangeEnd, $dateString) {
                $query->whereBetween('recordTime', [
                    $rangeStart,
                    $rangeEnd,
                ])
                    ->orWhere(function ($nested) use ($dateString) {
                        $nested->whereNull('recordTime')
                            ->whereDate('date', $dateString);
                    });
            })
            ->orderBy('deviceUserId', 'asc')
            ->orderBy('recordTime', 'asc')
            ->orderBy('time', 'asc')
            ->get()
            ->groupBy(fn (RawDeviceData $rawData): string => (string) $rawData->deviceUserId);

        if ($rawGroups->isEmpty()) {
            return [
                'date' => $dateString,
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'total_groups' => 0,
            ];
        }

        $employeeByDeviceUserId = Employee::query()
            ->select(['id', 'user_id', 'name', 'device_user_id', 'shift_id'])
            ->whereIn('device_user_id', $rawGroups->keys()->all(), 'and', false)
            ->with(['user:id,name', 'shift:id,name,start_time,end_time'])
            ->get()
            ->keyBy(fn (Employee $employee): string => (string) $employee->device_user_id);

        $existingByDeviceUserId = Attendance::query()
            ->whereDate('date', $dateString)
            ->whereIn('device_user_id', $rawGroups->keys()->all(), 'and', false)
            ->get()
            ->groupBy(fn (Attendance $attendance): string => (string) $attendance->device_user_id)
            ->map(function ($attendances) {
                $latestAttendance = $attendances->sortByDesc('updated_at')->first();

                $duplicateIds = $attendances
                    ->pluck('id')
                    ->reject(fn (int $id): bool => $id === $latestAttendance->id)
                    ->values();

                if ($duplicateIds->isNotEmpty()) {
                    Attendance::query()->whereIn('id', $duplicateIds->all(), 'and', false)->delete();
                }

                return $latestAttendance;
            });

        $defaultShift = Shift::default();

        $created = 0;
        $updated = 0;
        $unchanged = 0;

        foreach ($rawGroups as $deviceUserId => $records) {
            $normalizedDeviceUserId = trim((string) $deviceUserId);
            if ($normalizedDeviceUserId === '') {
                continue;
            }

            $orderedPunches = $records
                ->map(fn (RawDeviceData $rawData): Carbon => $this->resolvePunchDateTime($rawData))
                ->sort()
                ->values();

            if ($orderedPunches->isEmpty()) {
                continue;
            }

            $employee = $employeeByDeviceUserId->get($normalizedDeviceUserId);
            $existingAttendance = $existingByDeviceUserId->get($normalizedDeviceUserId);

            // 1st punch of the day is check-in, 2nd punch is check-out. Any
            // further punches that day are ignored for this purpose (no lunch
            // tracking, no time-of-day windowing).
            $checkIn = $orderedPunches->get(0);
            $checkOut = $orderedPunches->get(1);

            $lastPunch = $orderedPunches->last();

            $totalWorkMinutes = $this->calculateMinutes($checkIn, $checkOut);
            $overtimeMinutes = $totalWorkMinutes !== null ? max($totalWorkMinutes - 480, 0) : null;

            $schedule = $this->resolveScheduleThresholds($dateString, $employee?->shift ?? $defaultShift);
            $lateThreshold = $schedule['late_threshold'];
            $officeEndStart = $schedule['office_end_start'];

            $isLate = $checkIn !== null && $checkIn->gt($lateThreshold);
            $lateDurationMinutes = $isLate
                ? (int) $lateThreshold->diffInMinutes($checkIn)
                : 0;

            $isEarlyLeave = $checkOut !== null && $checkOut->lt($officeEndStart);
            $earlyLeaveMinutes = $isEarlyLeave
                ? (int) $checkOut->diffInMinutes($officeEndStart)
                : 0;

            $remarks = $this->buildRemarks(
                totalWorkMinutes: $totalWorkMinutes,
                overtimeMinutes: $overtimeMinutes,
                lateDurationMinutes: $lateDurationMinutes,
                earlyLeaveMinutes: $earlyLeaveMinutes,
            );

            $signature = sha1(implode('|', $orderedPunches->map(fn (Carbon $punch): string => $punch->format('Y-m-d H:i:s'))->all()));

            if ($existingAttendance !== null && $existingAttendance->raw_punch_signature === $signature) {
                $unchanged++;

                continue;
            }

            $payload = [
                'employee_id' => $employee?->id,
                'user_id' => $employee?->user_id,
                'date' => $dateString,
                'device_user_id' => $normalizedDeviceUserId,
                'employee_name' => $employee?->user?->name ?? $employee?->name ?? (string) ($records->first()->employeeName ?? 'Unknown'),
                'check_in' => $checkIn?->format('Y-m-d H:i:s'),
                'lunch_start' => null,
                'lunch_end' => null,
                'check_out' => $checkOut?->format('Y-m-d H:i:s'),
                'lunch_duration_minutes' => null,
                'total_work_minutes' => $totalWorkMinutes,
                'overtime_minutes' => $overtimeMinutes,
                'late_status' => $isLate,
                'late_duration_minutes' => $lateDurationMinutes,
                'early_leave_status' => $isEarlyLeave,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'remarks' => $remarks,
                'record_time' => $lastPunch?->format('Y-m-d H:i:s'),
                'record_date' => $dateString,
                'record_time_only' => $lastPunch?->format('H:i:s'),
                'timezone' => $records->first()->timeZone ?? 'UTC',
            ];

            if ($existingAttendance !== null) {
                $existingAttendance->update($payload);
                $updated++;

                continue;
            }

            Attendance::create($payload);
            $created++;
        }

        return [
            'date' => $dateString,
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'total_groups' => $rawGroups->count(),
        ];
    }

    /**
     * Resolve the late/early-leave thresholds for a date, based on the employee's
     * assigned shift. Falls back to the global attendance settings when no shift
     * is available (e.g. the shifts table is empty).
     *
     * Thresholds are parsed as UTC to match resolvePunchDateTime(), which reads
     * raw device punches as-is with no timezone conversion. Parsing with the
     * app's default timezone (e.g. Asia/Dhaka) here would shift the threshold
     * by the UTC offset and make on-time check-ins register as late.
     *
     * @return array{late_threshold:Carbon,office_end_start:Carbon}
     */
    protected function resolveScheduleThresholds(string $dateString, ?Shift $shift = null): array
    {
        if ($shift !== null) {
            return [
                'late_threshold' => Carbon::parse($dateString.' '.$shift->start_time, 'UTC'),
                'office_end_start' => Carbon::parse($dateString.' '.$shift->end_time, 'UTC'),
            ];
        }

        $settings = AttendanceSettings::current();

        $lateThreshold = config('attendance.schedule.office_start_late_threshold', '10:30:00');
        $officeEndStart = config('attendance.schedule.office_end_start', '17:30:00');

        if ($settings->working_hours_end !== null) {
            $officeEndStart = Carbon::parse($settings->working_hours_end)->format('H:i:s');
        }

        return [
            'late_threshold' => Carbon::parse($dateString.' '.$lateThreshold, 'UTC'),
            'office_end_start' => Carbon::parse($dateString.' '.$officeEndStart, 'UTC'),
        ];
    }

    protected function buildRemarks(?int $totalWorkMinutes, ?int $overtimeMinutes, int $lateDurationMinutes, int $earlyLeaveMinutes): string
    {
        if ($totalWorkMinutes === null) {
            return 'Insufficient punch data';
        }

        $remarks = [];

        if ($lateDurationMinutes > 0) {
            $remarks[] = 'Late by '.$this->humanizeMinutes($lateDurationMinutes);
        }

        if ($earlyLeaveMinutes > 0) {
            $remarks[] = 'Early leave by '.$this->humanizeMinutes($earlyLeaveMinutes);
        }

        if (($overtimeMinutes ?? 0) > 0) {
            $remarks[] = 'Overtime: '.$this->humanizeMinutes($overtimeMinutes);
        }

        if ($remarks === []) {
            return 'Worked '.$this->humanizeMinutes($totalWorkMinutes);
        }

        return implode('; ', $remarks);
    }

    protected function humanizeMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' '.Str::plural('hour', $hours);
        }

        if ($remainingMinutes > 0) {
            $parts[] = $remainingMinutes.' '.Str::plural('minute', $remainingMinutes);
        }

        if ($parts === []) {
            return '0 minutes';
        }

        return implode(' ', $parts);
    }

    protected function resolvePunchDateTime(RawDeviceData $rawData): Carbon
    {
        // No timezone conversion: the raw punch timestamp is used as-is so
        // punches sort chronologically for the 1st-punch/2nd-punch rule above.
        if ($rawData->recordTime !== null) {
            return Carbon::parse($rawData->recordTime, 'UTC');
        }

        return Carbon::parse($rawData->date.' '.$rawData->time, 'UTC');
    }

    protected function calculateMinutes(?Carbon $start, ?Carbon $end): ?int
    {
        if ($start === null || $end === null) {
            return null;
        }

        if ($end->lt($start)) {
            return 0;
        }

        return (int) $start->diffInMinutes($end);
    }
}
