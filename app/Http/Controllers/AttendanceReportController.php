<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSettings;
use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportController extends Controller
{
    /**
     * Display the monthly employee attendance report.
     */
    public function index(Request $request)
    {
        return view('dashboard.attendancereport', $this->buildReportContext($request));
    }

    /**
     * Export the currently filtered monthly attendance report to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $context = $this->buildReportContext($request);

        $filenameSuffix = $context['employee']
            ? '-'.str($context['employee']->user?->name ?? $context['employee']->name ?? 'employee')->slug()
            : '';

        $filename = "attendance-report-{$context['month']}{$filenameSuffix}.csv";

        return response()->streamDownload(function () use ($context): void {
            $csv = fopen('php://output', 'w');

            if ($context['employee']) {
                fputcsv($csv, ['Date', 'Day', 'Status', 'Check In', 'Check Out', 'Hours', 'Overtime', 'Remarks']);

                foreach ($context['days'] as $day) {
                    fputcsv($csv, [
                        $day['date']->format('Y-m-d'),
                        $day['date']->format('l'),
                        $day['status'],
                        $day['check_in'] ?? '',
                        $day['check_out'] ?? '',
                        $day['hours'] ?? '',
                        $day['overtime'] ?? '',
                        $day['remarks'] ?? '',
                    ]);
                }
            } else {
                fputcsv($csv, ['Employee', 'Department', 'Present', 'Late', 'Absent', 'Total Hours']);

                foreach ($context['summaries'] as $row) {
                    fputcsv($csv, [
                        $row['name'],
                        $row['department'] ?? '',
                        $row['present'],
                        $row['late'],
                        $row['absent'],
                        $row['hours'],
                    ]);
                }
            }

            fclose($csv);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Build the shared data needed by both the report view and the CSV export.
     *
     * @return array<string, mixed>
     */
    protected function buildReportContext(Request $request): array
    {
        $monthInput = (string) $request->input('month', '');
        $month = preg_match('/^\d{4}-\d{2}$/', $monthInput) ? $monthInput : now()->format('Y-m');

        $monthStart = Carbon::parse($month.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $today = Carbon::today();

        $status = in_array($request->input('status'), ['active', 'inactive', 'separated', 'all'], true)
            ? $request->input('status')
            : 'active';

        $employees = Employee::with('user')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->get();

        $employeeId = $request->filled('employee_id') ? (int) $request->input('employee_id') : null;
        $employee = $employeeId
            ? ($employees->firstWhere('id', $employeeId) ?? Employee::with('user')->find($employeeId))
            : null;

        $weekendDays = AttendanceSettings::current()->weekend_days ?? ['fri'];

        $holidays = Holiday::query()
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->keyBy(fn (Holiday $holiday) => $holiday->date->toDateString());

        $days = [];
        $summaries = [];
        $kpis = [];

        if ($employee) {
            [$days, $kpis] = $this->buildEmployeeDays($employee, $monthStart, $monthEnd, $today, $weekendDays, $holidays);
        } else {
            [$summaries, $kpis] = $this->buildEmployeeSummaries($employees, $monthStart, $monthEnd, $today, $weekendDays, $holidays);
        }

        return [
            'month' => $month,
            'monthLabel' => $monthStart->format('F Y'),
            'employees' => $employees,
            'employee' => $employee,
            'employeeId' => $employeeId,
            'status' => $status,
            'weekendDays' => $weekendDays,
            'days' => $days,
            'summaries' => $summaries,
            'kpis' => $kpis,
        ];
    }

    /**
     * Build the day-by-day breakdown for a single employee within the given month.
     *
     * @param  array<int, string>  $weekendDays
     * @param  Collection<string, Holiday>  $holidays
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, mixed>}
     */
    protected function buildEmployeeDays(Employee $employee, Carbon $monthStart, Carbon $monthEnd, Carbon $today, array $weekendDays, $holidays): array
    {
        $attendances = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->keyBy(fn (Attendance $attendance) => $attendance->date->toDateString());

        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;
        $weekendCount = 0;
        $holidayCount = 0;
        $totalMinutes = 0;
        $overtimeMinutes = 0;
        $days = [];

        foreach (CarbonPeriod::create($monthStart, $monthEnd) as $date) {
            $dateKey = $date->toDateString();
            $holiday = $holidays->get($dateKey);
            $isWeekend = in_array(strtolower($date->format('D')), $weekendDays, true);
            $attendance = $attendances->get($dateKey);

            $status = 'Upcoming';

            if ($holiday) {
                $status = 'Holiday';
                $holidayCount++;
            } elseif ($isWeekend) {
                $status = 'Weekend';
                $weekendCount++;
            } elseif ($attendance && $attendance->check_in) {
                $status = $attendance->late_status ? 'Late' : 'Present';
                $presentCount++;
                $totalMinutes += $attendance->total_work_minutes ?? 0;
                $overtimeMinutes += $attendance->overtime_minutes ?? 0;

                if ($attendance->late_status) {
                    $lateCount++;
                }
            } elseif ($date->lte($today)) {
                $status = 'Absent';
                $absentCount++;
            }

            $days[] = [
                'date' => $date->copy(),
                'status' => $status,
                'holiday_name' => $holiday?->name,
                'check_in' => $attendance?->check_in?->format('h:i A'),
                'check_out' => $attendance?->check_out?->format('h:i A'),
                'hours' => $attendance?->total_work_minutes !== null ? $this->formatMinutes($attendance->total_work_minutes) : null,
                'overtime' => $attendance?->overtime_minutes !== null ? $this->formatMinutes($attendance->overtime_minutes) : null,
                'remarks' => $attendance?->remarks,
            ];
        }

        $kpis = [
            'present' => $presentCount,
            'late' => $lateCount,
            'absent' => $absentCount,
            'weekend' => $weekendCount,
            'holiday' => $holidayCount,
            'hours' => $this->formatMinutes($totalMinutes),
            'overtime_hours' => $this->formatMinutes($overtimeMinutes),
        ];

        return [$days, $kpis];
    }

    /**
     * Build a per-employee monthly summary for every employee.
     *
     * @param  Collection<int, Employee>  $employees
     * @param  array<int, string>  $weekendDays
     * @param  Collection<string, Holiday>  $holidays
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, mixed>}
     */
    protected function buildEmployeeSummaries($employees, Carbon $monthStart, Carbon $monthEnd, Carbon $today, array $weekendDays, $holidays): array
    {
        $elapsedEnd = $today->lt($monthEnd) ? $today : $monthEnd;

        $workingDaysElapsed = 0;

        if ($elapsedEnd->gte($monthStart)) {
            foreach (CarbonPeriod::create($monthStart, $elapsedEnd) as $date) {
                $isWeekend = in_array(strtolower($date->format('D')), $weekendDays, true);

                if (! $isWeekend && ! $holidays->has($date->toDateString())) {
                    $workingDaysElapsed++;
                }
            }
        }

        $attendancesByEmployee = Attendance::query()
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->groupBy('employee_id');

        $summaries = [];

        foreach ($employees as $emp) {
            $records = $attendancesByEmployee->get($emp->id, collect());
            $present = $records->whereNotNull('check_in')->count();
            $late = $records->where('late_status', true)->count();
            $hours = (int) $records->sum('total_work_minutes');

            $summaries[] = [
                'id' => $emp->id,
                'name' => $emp->user?->name ?? $emp->name ?? 'N/A',
                'department' => $emp->department,
                'present' => $present,
                'late' => $late,
                'absent' => max($workingDaysElapsed - $present, 0),
                'hours' => $this->formatMinutes($hours),
            ];
        }

        $kpis = [
            'total_employees' => $employees->count(),
            'working_days' => $workingDaysElapsed,
            'total_present' => array_sum(array_column($summaries, 'present')),
            'total_late' => array_sum(array_column($summaries, 'late')),
        ];

        return [$summaries, $kpis];
    }

    protected function formatMinutes(?int $minutes): string
    {
        if (! $minutes) {
            return '0h';
        }

        return rtrim(rtrim(number_format($minutes / 60, 2, '.', ''), '0'), '.').'h';
    }
}
