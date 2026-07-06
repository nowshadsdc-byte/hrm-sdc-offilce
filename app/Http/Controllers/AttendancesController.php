<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSettings;
use App\Models\Device;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendancesController extends Controller
{
    /**
     * Display a listing of attendances.
     */
    public function index(Request $request)
    {
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->string('date'))->startOfDay()
            : now()->startOfDay();

        $workingHoursStart = AttendanceSettings::current()->working_hours_start;
        $workingStartTime = $workingHoursStart ? Carbon::parse($workingHoursStart)->format('H:i:s') : '09:00:00';

        $presentCount = Attendance::query()
            ->whereRaw('DATE(date) = ?', [$selectedDate->toDateString()], 'and')
            ->where('check_in', '!=', null)
            ->distinct('employee_id')
            ->count('employee_id');

        $lateCount = Attendance::query()
            ->whereRaw('DATE(date) = ?', [$selectedDate->toDateString()], 'and')
            ->where('check_in', '!=', null)
            ->whereRaw('TIME(check_in) > ?', [$workingStartTime], 'and')
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

        $query = Attendance::query();

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

        return view('dashboard.attendance', [
            'kpis' => $kpis,
            'selectedDate' => $selectedDate->format('m/d/Y'),
            'attendances' => $attendances,
            'employees' => Employee::with('user')->get(),
            'devices' => Device::all(),
            'filters' => $request->only(['start_date', 'end_date', 'employee_id', 'device_id']),
        ]);
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
    public function export(Request $request)
    {
        $query = Attendance::query();

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $attendances = $query->with(['employee.user', 'device'])->get();

        $headers = ['Date', 'Employee', 'Check-In', 'Check-Out', 'Device', 'Duration (Hours)'];
        $data = $attendances->map(function ($attendance) {
            $checkOut = $attendance->check_out ? $attendance->check_out->format('Y-m-d H:i:s') : 'N/A';
            $duration = $attendance->check_out ? $attendance->check_out->diffInHours($attendance->check_in) : 'N/A';

            return [
                $attendance->date->format('Y-m-d'),
                $attendance->employee->user->name,
                $attendance->check_in->format('Y-m-d H:i:s'),
                $checkOut,
                $attendance->device?->name ?? 'N/A',
                $duration,
            ];
        });

        $csv = implode(',', $headers)."\n";
        foreach ($data as $row) {
            $csv .= implode(',', $row)."\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="attendances.csv"');
    }
}
