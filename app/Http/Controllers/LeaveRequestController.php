<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'all');

        $query = LeaveRequest::with(['user', 'employee', 'approver'])->orderBy('created_at', 'desc');

        if ($tab === 'pending') {
            $query->where('status', 'pending');
        } elseif ($tab === 'approved') {
            $query->where('status', 'approved');
        } elseif ($tab === 'rejected') {
            $query->where('status', 'rejected');
        }

        $leaveRequests = $query->get();

        $stats = [
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
        ];

        $employees = Employee::all();
        $leaveTypes = ['Casual Leave', 'Sick Leave', 'Annual Leave', 'Maternity Leave', 'Paternity Leave'];

        return view('dashboard.leave-requests', compact('leaveRequests', 'stats', 'tab', 'employees', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'pending';

        LeaveRequest::create($validated);

        return back()->with('success', 'Leave request submitted successfully.');
    }

    public function approve(LeaveRequest $leaveRequest)
    {
        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('success', 'Leave request rejected.');
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        $leaveRequest->delete();

        return back()->with('success', 'Leave request deleted.');
    }
}
