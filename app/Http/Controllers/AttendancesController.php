<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;
use Illuminate\Http\Request;

class AttendancesController extends Controller
{
    /**
     * Display a listing of attendances.
     */
    public function index(Request $request)
    {
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

        return inertia('Attendances/Index', [
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
        $attendance->delete();

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
