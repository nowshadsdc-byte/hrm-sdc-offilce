<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAttendanceAdjustmentRequest;
use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\AttendanceSyncService;
use App\Services\SmartAttendanceSyncService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendancesController extends Controller
{
    public function __construct(
        protected AttendanceSyncService $attendanceSyncService,
        protected SmartAttendanceSyncService $smartAttendanceSyncService,
    ) {}

    /**
     * Display a listing of attendances.
     */
    public function index(Request $request)
    {
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->string('date'))->startOfDay()
            : now()->startOfDay();

        $lateThreshold = config('attendance.schedule.office_start_late_threshold', '10:30:00');

        $presentCount = Attendance::query()
            ->whereRaw('DATE(date) = ?', [$selectedDate->toDateString()], 'and')
            ->where('check_in', '!=', null)
            ->distinct('employee_id')
            ->count('employee_id');

        $lateCount = Attendance::query()
            ->whereRaw('DATE(date) = ?', [$selectedDate->toDateString()], 'and')
            ->where('check_in', '!=', null)
            ->whereRaw('TIME(check_in) > ?', [$lateThreshold], 'and')
            ->distinct('employee_id')
            ->count('employee_id');

        $onLeaveCount = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereRaw('DATE(start_date) <= ?', [$selectedDate->toDateString()], 'and')
            ->whereRaw('DATE(end_date) >= ?', [$selectedDate->toDateString()], 'and')
            ->distinct('employee_id')
            ->count('employee_id');

        $totalEmployees = Employee::query()->count('*');
        $absentCount = max($totalEmployees - $presentCount - $onLeaveCount, 0);

        $kpis = [
            [
                'label' => 'Present',
                'value' => $presentCount,
                'icon_class' => 'stat-icon-success',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>',
                'change_class' => 'stat-change-up',
                'change_icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><path stroke-linecap="round" stroke-linejoin="round" d="M18 15l-6-6-6 6"/></svg>',
                'change_text' => 'Today',
            ],
            [
                'label' => 'Late',
                'value' => $lateCount,
                'icon_class' => 'stat-icon-warning',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
                'change_class' => 'stat-change-down',
                'change_icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>',
                'change_text' => 'Today',
            ],
            [
                'label' => 'Absent',
                'value' => $absentCount,
                'icon_class' => 'stat-icon-danger',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/></svg>',
                'change_class' => 'stat-change-down',
                'change_icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>',
                'change_text' => 'Today',
            ],
            [
                'label' => 'On Leave',
                'value' => $onLeaveCount,
                'icon_class' => 'stat-icon-info',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 13c2 0 2-2 4-2s2 2 4 2 2-2 4-2"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 18c2 0 2-2 4-2s2 2 4 2 2-2 4-2 2 2 4 2"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 8c1.5-2.5 5-3.5 7-1 2-2.5 5.5-1.5 7 1"/></svg>',
                'change_class' => 'stat-change-up',
                'change_icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><path stroke-linecap="round" stroke-linejoin="round" d="M18 15l-6-6-6 6"/></svg>',
                'change_text' => 'Today',
            ],
        ];

        $query = Attendance::query()
            ->whereDate('date', $selectedDate->toDateString());

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by device
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        $attendances = $query
            ->with(['employee.user', 'device'])
            ->orderByDesc('date')
            ->paginate(50);

        $user = $request->user();
        $canAdjustAttendance = $user !== null
            && method_exists($user, 'hasRole')
            && $user->hasRole('super-admin');

        return view('dashboard.attendance', [
            'kpis' => $kpis,
            'selectedDate' => $selectedDate->toDateString(),
            'attendances' => $attendances,
            'employees' => Employee::with('user')->get(),
            'devices' => Device::all(),
            'filters' => $request->only(['start_date', 'end_date', 'employee_id', 'device_id']),
            'canAdjustAttendance' => $canAdjustAttendance,
        ]);
    }

    /**
     * Manually adjust attendance times and metrics (Super Admin only).
     */
    public function adjust(UpdateAttendanceAdjustmentRequest $request, Attendance $attendance): RedirectResponse
    {
        $validated = $request->validated();
        $dateString = $attendance->date?->toDateString() ?? now()->toDateString();

        $checkIn = $this->combineDateAndTime($dateString, $validated['check_in_time'] ?? null);
        $lunchStart = $this->combineDateAndTime($dateString, $validated['lunch_start_time'] ?? null);
        $lunchEnd = $this->combineDateAndTime($dateString, $validated['lunch_end_time'] ?? null);
        $checkOut = $this->combineDateAndTime($dateString, $validated['check_out_time'] ?? null);

        $lunchStartAction = $validated['lunch_start_action'] ?? 'keep';
        $lunchEndAction = $validated['lunch_end_action'] ?? 'keep';

        if ($lunchStartAction === 'remove') {
            $lunchStart = null;
        } elseif ($lunchStartAction === 'use_as_check_out') {
            if ($lunchStart !== null) {
                $checkOut = $lunchStart;
            }

            $lunchStart = null;
        }

        if ($lunchEndAction === 'remove') {
            $lunchEnd = null;
        } elseif ($lunchEndAction === 'use_as_check_out') {
            if ($lunchEnd !== null) {
                $checkOut = $lunchEnd;
            }

            $lunchEnd = null;
        }

        $lunchDurationMinutes = array_key_exists('lunch_duration_minutes', $validated) && $validated['lunch_duration_minutes'] !== null
            ? (int) $validated['lunch_duration_minutes']
            : $this->calculateMinutesBetween($lunchStart, $lunchEnd);

        $totalWorkMinutes = array_key_exists('total_work_minutes', $validated) && $validated['total_work_minutes'] !== null
            ? (int) $validated['total_work_minutes']
            : $this->calculateMinutesBetween($checkIn, $checkOut);

        $overtimeMinutes = array_key_exists('overtime_minutes', $validated) && $validated['overtime_minutes'] !== null
            ? (int) $validated['overtime_minutes']
            : ($totalWorkMinutes !== null ? max($totalWorkMinutes - 480, 0) : null);

        $lateThreshold = config('attendance.schedule.office_start_late_threshold', '10:30:00');
        $lateThresholdAt = Carbon::parse($dateString.' '.$lateThreshold);
        $isLate = $checkIn !== null && $checkIn->gt($lateThresholdAt);
        $lateDurationMinutes = $checkIn !== null && $checkIn->gt($lateThresholdAt)
            ? $lateThresholdAt->diffInMinutes($checkIn)
            : 0;

        $attendance->update([
            'check_in' => $checkIn?->format('Y-m-d H:i:s'),
            'lunch_start' => $lunchStart?->format('Y-m-d H:i:s'),
            'lunch_end' => $lunchEnd?->format('Y-m-d H:i:s'),
            'check_out' => $checkOut?->format('Y-m-d H:i:s'),
            'lunch_duration_minutes' => $lunchDurationMinutes,
            'total_work_minutes' => $totalWorkMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'late_status' => $isLate,
            'late_duration_minutes' => $lateDurationMinutes,
            'remarks' => $validated['remarks'] ?? $attendance->remarks,
        ]);

        return redirect()
            ->route('dashboard.attendance', ['date' => $request->input('date', $dateString)])
            ->with('success', 'Attendance record updated successfully.');
    }

    protected function combineDateAndTime(string $dateString, ?string $time): ?Carbon
    {
        if ($time === null || $time === '') {
            return null;
        }

        return Carbon::parse($dateString.' '.$time);
    }

    protected function calculateMinutesBetween(?Carbon $start, ?Carbon $end): ?int
    {
        if ($start === null || $end === null) {
            return null;
        }

        if ($end->lt($start)) {
            return 0;
        }

        return $start->diffInMinutes($end);
    }

    /**
     * Process raw punch data for a selected date and refresh attendance records.
     */
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'date.before_or_equal' => 'Selected date cannot be in the future.',
        ]);

        $selectedDate = Carbon::parse($validated['date'])->startOfDay();
        $result = $this->attendanceSyncService->syncForDate($selectedDate);

        $message = 'Attendance data updated successfully.';

        if ($result['total_groups'] === 0) {
            $message = 'No raw punch records found for the selected date.';
        }

        return redirect()->route('dashboard.attendance', ['date' => $selectedDate->toDateString()])
            ->with('success', $message);
    }

    /**
     * Smart sync: pull fresh data if today, then sync attendance records.
     * Streams progress events to the client.
     */
    public function syncNow(Request $request): StreamedResponse
    {
        return response()->stream(function () use ($request) {
            // Disable buffering so progress events reach the browser immediately.
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');

            $validated = $request->validate([
                'date' => ['required', 'date', 'before_or_equal:today'],
            ], [
                'date.before_or_equal' => 'Selected date cannot be in the future.',
            ]);

            $selectedDate = Carbon::parse($validated['date'])->startOfDay();

            try {
                $result = $this->smartAttendanceSyncService->sync($selectedDate, function (array $progress) {
                    echo json_encode($progress)."\n";
                    @ob_flush();
                    flush();
                });

                if ($result['total_groups'] === 0) {
                    echo json_encode([
                        'type' => 'info',
                        'message' => $result['message'],
                    ])."\n";
                    @ob_flush();
                    flush();
                } else {
                    echo json_encode([
                        'type' => 'success',
                        'message' => $result['message'],
                        'stats' => [
                            'created' => $result['created'],
                            'updated' => $result['updated'],
                            'unchanged' => $result['unchanged'],
                        ],
                    ])."\n";
                    @ob_flush();
                    flush();
                }
            } catch (\Throwable $e) {
                echo json_encode([
                    'type' => 'error',
                    'message' => 'Sync error: '.$e->getMessage(),
                ])."\n";
                @ob_flush();
                flush();
            }
        });
    }

    /**
     * Show the form for creating a new attendance record.
     */
    public function create()
    {
        return view('dashboard.attendance-create', [
            'employees' => Employee::with('user')->get(),
            'devices' => Device::all(),
        ]);
    }

    /**
     * Store a newly created attendance record in database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'user_id' => 'required|exists:users,id',
            'device_id' => 'nullable|exists:devices,id',
            'check_in' => 'required|date_format:Y-m-d H:i:s',
            'check_out' => 'nullable|date_format:Y-m-d H:i:s|after:check_in',
            'date' => 'required|date_format:Y-m-d',
        ]);

        Attendance::create($validated);

        return redirect()->route('attendances.index')->with('success', 'Attendance record created successfully');
    }

    /**
     * Display the specified attendance record.
     */
    public function show(Attendance $attendance)
    {
        return inertia('Attendances/Show', [
            'attendance' => $attendance->load(['employee.user', 'device']),
        ]);
    }

    /**
     * Show the form for editing the specified attendance record.
     */
    public function edit(Attendance $attendance)
    {
        return inertia('Attendances/Edit', [
            'attendance' => $attendance->load(['employee.user', 'device']),
            'employees' => Employee::with('user')->get(),
            'devices' => Device::all(),
        ]);
    }

    /**
     * Update the specified attendance record in database.
     */
    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'user_id' => 'required|exists:users,id',
            'device_id' => 'nullable|exists:devices,id',
            'check_in' => 'required|date_format:Y-m-d H:i:s',
            'check_out' => 'nullable|date_format:Y-m-d H:i:s|after:check_in',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $attendance->update($validated);

        return redirect()->route('attendances.index')->with('success', 'Attendance record updated successfully');
    }

    /**
     * Remove the specified attendance record from database.
     */
    public function destroy(Attendance $attendance)
    {
        Attendance::destroy($attendance->id);

        return redirect()->route('attendances.index')->with('success', 'Attendance record deleted successfully');
    }

    /**
     * Export attendance records to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ]);

        $startDate = $validated['start_date'] ?? now()->toDateString();
        $endDate = $validated['end_date'] ?? $startDate;

        $attendances = Attendance::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->when(
                isset($validated['employee_id']),
                fn ($query) => $query->where('employee_id', $validated['employee_id']),
            )
            ->with(['employee.user', 'device'])
            ->orderBy('date')
            ->orderBy('check_in')
            ->get();

        return response()->streamDownload(function () use ($attendances): void {
            $csv = fopen('php://output', 'w');

            fputcsv($csv, ['Date', 'Employee', 'Check In', 'Lunch Start', 'Lunch End', 'Check Out', 'Hours', 'Overtime', 'Late Status', 'Remarks']);

            foreach ($attendances as $attendance) {
                fputcsv($csv, [
                    $attendance->date?->format('Y-m-d') ?? '',
                    $attendance->employee?->user?->name ?? $attendance->employee?->name ?? $attendance->employee_name ?? 'N/A',
                    $attendance->check_in?->format('h:i A') ?? '',
                    $attendance->lunch_start?->format('h:i A') ?? '',
                    $attendance->lunch_end?->format('h:i A') ?? '',
                    $attendance->check_out?->format('h:i A') ?? '',
                    $attendance->total_work_minutes !== null ? rtrim(rtrim(number_format($attendance->total_work_minutes / 60, 2, '.', ''), '0'), '.').'h' : '',
                    $attendance->overtime_minutes !== null ? rtrim(rtrim(number_format($attendance->overtime_minutes / 60, 2, '.', ''), '0'), '.').'h' : '',
                    $attendance->late_status ? 'Late' : 'On Time',
                    $attendance->remarks ?? '',
                ]);
            }

            fclose($csv);
        }, "attendances-{$startDate}-to-{$endDate}.csv", ['Content-Type' => 'text/csv']);
    }
}
