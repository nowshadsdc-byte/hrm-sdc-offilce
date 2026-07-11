<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSettings;
use App\Models\Employee;
use App\Models\RawDeviceData;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AttendanceSyncService
{
    /**
     * @return array{date:string,created:int,updated:int,unchanged:int,total_groups:int}
     */
    public function syncForDate(Carbon $selectedDate): array
    {
        $date = $selectedDate->copy()->startOfDay();
        $dateString = $date->toDateString();
        $isHistoricalDate = $date->lt(now()->startOfDay());

        $rawGroups = RawDeviceData::query()
            ->where(function ($query) use ($dateString) {
                $query->whereDate('recordTime', $dateString)
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
            ->select(['id', 'user_id', 'name', 'device_user_id'])
            ->whereIn('device_user_id', $rawGroups->keys()->all(), 'and', false)
            ->with(['user:id,name'])
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

        $schedule = $this->resolveScheduleThresholds($dateString);

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
            $punchCount = $orderedPunches->count();

            if ($orderedPunches->isEmpty()) {
                continue;
            }

            $employee = $employeeByDeviceUserId->get($normalizedDeviceUserId);
            $existingAttendance = $existingByDeviceUserId->get($normalizedDeviceUserId);

            $checkIn = $orderedPunches->get(0);
            $lunchStart = $orderedPunches->get(1);
            $lunchEnd = $orderedPunches->get(2);
            $checkOut = $orderedPunches->get(3);

            if ($isHistoricalDate && $punchCount === 2) {
                $lunchStart = null;
                $lunchEnd = null;
                $checkOut = $orderedPunches->get(1);
            }

            $lastPunch = $orderedPunches->last();

            $lunchDurationMinutes = $this->calculateMinutes($lunchStart, $lunchEnd);
            $totalWorkMinutes = $this->calculateMinutes($checkIn, $checkOut);
            $overtimeMinutes = $totalWorkMinutes !== null ? max($totalWorkMinutes - 480, 0) : null;

            $lateThreshold = $schedule['late_threshold'];
            $officeEndStart = $schedule['office_end_start'];

            $isLate = $checkIn !== null && $checkIn->gt($lateThreshold);
            $lateDurationMinutes = $checkIn !== null && $checkIn->gt($lateThreshold)
                ? $lateThreshold->diffInMinutes($checkIn)
                : 0;
            $earlyDepartureMinutes = $checkOut !== null && $checkOut->lt($officeEndStart)
                ? $checkOut->diffInMinutes($officeEndStart)
                : 0;

            $remarks = $this->buildRemarks(
                totalWorkMinutes: $totalWorkMinutes,
                overtimeMinutes: $overtimeMinutes,
                lateDurationMinutes: $lateDurationMinutes,
                earlyDepartureMinutes: $earlyDepartureMinutes,
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
                'lunch_start' => $lunchStart?->format('Y-m-d H:i:s'),
                'lunch_end' => $lunchEnd?->format('Y-m-d H:i:s'),
                'check_out' => $checkOut?->format('Y-m-d H:i:s'),
                'lunch_duration_minutes' => $lunchDurationMinutes,
                'total_work_minutes' => $totalWorkMinutes,
                'overtime_minutes' => $overtimeMinutes,
                'late_status' => $isLate,
                'late_duration_minutes' => $lateDurationMinutes,
                'remarks' => $remarks,
                'record_time' => $lastPunch?->format('Y-m-d H:i:s'),
                'record_date' => $dateString,
                'record_time_only' => $lastPunch?->format('H:i:s'),
                'last_raw_punch_at' => $lastPunch?->format('Y-m-d H:i:s'),
                'raw_punch_signature' => $signature,
                'last_synced_at' => now(),
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
     * @return array{late_threshold:Carbon,office_end_start:Carbon}
     */
    protected function resolveScheduleThresholds(string $dateString): array
    {
        $settings = AttendanceSettings::current();

        $lateThreshold = config('attendance.schedule.office_start_late_threshold', '10:30:00');
        $officeEndStart = config('attendance.schedule.office_end_start', '17:30:00');

        if ($settings->working_hours_end !== null) {
            $officeEndStart = Carbon::parse($settings->working_hours_end)->format('H:i:s');
        }

        return [
            'late_threshold' => Carbon::parse($dateString.' '.$lateThreshold),
            'office_end_start' => Carbon::parse($dateString.' '.$officeEndStart),
        ];
    }

    protected function buildRemarks(?int $totalWorkMinutes, ?int $overtimeMinutes, int $lateDurationMinutes, int $earlyDepartureMinutes): string
    {
        if ($totalWorkMinutes === null) {
            return 'Insufficient punch data';
        }

        $remarks = [];

        if ($lateDurationMinutes > 0) {
            $remarks[] = 'Late by '.$this->humanizeMinutes($lateDurationMinutes);
        }

        if ($earlyDepartureMinutes > 0) {
            $remarks[] = 'Early departure by '.$this->humanizeMinutes($earlyDepartureMinutes);
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
        if ($rawData->recordTime !== null) {
            return Carbon::parse($rawData->recordTime);
        }

        return Carbon::parse($rawData->date.' '.$rawData->time);
    }

    protected function calculateMinutes(?Carbon $start, ?Carbon $end): ?int
    {
        if ($start === null || $end === null) {
            return null;
        }

        if ($end->lt($start)) {
            return 0;
        }

        return $start->diffInMinutes($end);
    }
}
