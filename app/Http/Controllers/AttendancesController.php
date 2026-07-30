<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;
use App\Services\SmartAttendanceSyncService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendancesController extends Controller
{
    /**
     * Display a listing of attendance records.
     */
    public function index(Request $request, SmartAttendanceSyncService $attendanceSyncService)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'sync' => ['nullable', 'boolean'],
        ]);

        $dateString = $validated['date'] ?? now()->toDateString();
        $syncResult = null;

        $attendances = $this->loadAttendancesForDate($dateString);

        $shouldSync = $request->boolean('sync') || $attendances->isEmpty();

        if ($shouldSync) {
            $syncResult = $attendanceSyncService->sync(Carbon::parse($dateString));

            if ($syncResult['ok'] ?? true) {
                $attendances = $this->loadAttendancesForDate($dateString);
            }
        }

        $stats = $this->buildAttendanceStats($attendances);
        $isAdmin = $this->userIsAdmin($request);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'date' => $dateString,
                'dateLabel' => Carbon::parse($dateString)->format('l, F j, Y'),
                'syncResult' => $syncResult,
                'html' => view('dashboard.partials.attendance-panel', [
                    'date' => $dateString,
                    'attendances' => $attendances,
                    'syncResult' => $syncResult,
                    'stats' => $stats,
                    'isAdmin' => $isAdmin,
                ])->render(),
            ]);
        }

        return view('dashboard.attendance', [
            'date' => $dateString,
            'attendances' => $attendances,
            'syncResult' => $syncResult,
            'stats' => $stats,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Quick inline update for check-in/check-out time, status, and remarks
     * from the Daily Punches table. Restricted to admin roles.
     */
    public function quickUpdate(Request $request, Attendance $attendance)
    {
        abort_unless($this->userIsAdmin($request), 403);

        $validated = $request->validate([
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:on_time,late,early_leave,late_and_early_leave'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $dateString = $attendance->date?->toDateString() ?? now()->toDateString();

        $checkIn = $validated['check_in'] ? Carbon::parse($dateString.' '.$validated['check_in']) : null;
        $checkOut = $validated['check_out'] ? Carbon::parse($dateString.' '.$validated['check_out']) : null;

        if ($checkIn !== null && $checkOut !== null && $checkOut->lt($checkIn)) {
            throw ValidationException::withMessages([
                'check_out' => 'Check out must be after check in.',
            ]);
        }

        $attendance->update([
            'check_in' => $checkIn?->format('Y-m-d H:i:s'),
            'check_out' => $checkOut?->format('Y-m-d H:i:s'),
            'total_work_minutes' => ($checkIn !== null && $checkOut !== null) ? (int) $checkIn->diffInMinutes($checkOut) : null,
            'late_status' => in_array($validated['status'], ['late', 'late_and_early_leave'], true),
            'early_leave_status' => in_array($validated['status'], ['early_leave', 'late_and_early_leave'], true),
            'remarks' => $validated['remarks'] ?: null,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Attendance record updated successfully.',
        ]);
    }

    protected function userIsAdmin(Request $request): bool
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'hasAnyRole')) {
            return false;
        }

        return $user->hasAnyRole(config('tyro-dashboard.admin_roles', ['admin', 'super-admin']));
    }

    /**
     * @return Collection<int, Attendance>
     */
    protected function loadAttendancesForDate(string $dateString)
    {
        return Attendance::query()
            ->whereDate('date', $dateString)
            ->with(['employee.user'])
            ->orderBy('check_in')
            ->get();
    }

    /**
     * @param  Collection<int, Attendance>  $attendances
     * @return array{total:int,present:int,late:int,total_hours:float}
     */
    protected function buildAttendanceStats($attendances): array
    {
        return [
            'total' => $attendances->count(),
            'present' => $attendances->whereNotNull('check_in')->count(),
            'late' => $attendances->where('late_status', true)->count(),
            'total_hours' => round($attendances->sum('total_work_minutes') / 60, 1),
        ];
    }

    /**
     * Show the form for creating a new attendance record.
     */
    public function create()
    {
        return inertia('Attendances/Create', [
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

            fputcsv($csv, ['Date', 'Employee', 'Check In', 'Check Out', 'Hours', 'Overtime', 'Late Status', 'Remarks']);

            foreach ($attendances as $attendance) {
                fputcsv($csv, [
                    $attendance->date?->format('Y-m-d') ?? '',
                    $attendance->employee?->user?->name ?? $attendance->employee?->name ?? $attendance->employee_name ?? 'N/A',
                    $attendance->check_in_utc?->format('H:i:s') ?? '',
                    $attendance->check_out_utc?->format('H:i:s') ?? '',
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
