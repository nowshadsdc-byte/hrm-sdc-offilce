<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use HasinHayder\TyroDashboard\Support\DashboardRoute;
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

        $employeeLeaveBalances = $employees->mapWithKeys(
            fn (Employee $employee) => [$employee->id => $employee->leaveBalance()]
        );

        $user = $request->user();
        $isAdmin = $user !== null
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(config('tyro-dashboard.admin_roles', ['admin', 'super-admin']));

        return view('dashboard.leave-requests', compact(
            'leaveRequests', 'stats', 'tab', 'employees', 'leaveTypes', 'employeeLeaveBalances', 'isAdmin'
        ));
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

    public function myRequests(Request $request)
    {
        $user = $request->user();
        $employee = $user?->employee;
        $leaveTypes = ['Casual Leave', 'Sick Leave', 'Annual Leave', 'Maternity Leave', 'Paternity Leave'];
        $tab = $request->query('tab', 'all');

        $query = LeaveRequest::query()
            ->where('user_id', $user?->id)
            ->with(['employee'])
            ->orderByDesc('created_at');

        if ($tab === 'pending') {
            $query->where('status', 'pending');
        } elseif ($tab === 'approved') {
            $query->where('status', 'approved');
        } elseif ($tab === 'rejected') {
            $query->where('status', 'rejected');
        }

        $leaveRequests = $query->get();

        $stats = [
            'pending' => LeaveRequest::where('user_id', $user?->id)->where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('user_id', $user?->id)->where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('user_id', $user?->id)->where('status', 'rejected')->count(),
        ];

        $leaveBalance = $employee?->leaveBalance() ?? [
            'total' => 0,
            'approved' => 0,
            'pending' => 0,
            'remaining' => 0,
            'available' => 0,
        ];

        $leaveRequestDetails = $leaveRequests->map(fn (LeaveRequest $request) => [
            'id' => $request->id,
            'leave_type' => $request->leave_type,
            'status' => $request->status,
            'start_date' => $request->start_date?->format('M d, Y'),
            'end_date' => $request->end_date?->format('M d, Y'),
            'days_count' => $request->days_count,
            'reason' => $request->reason ?? '',
            'created_at' => $request->created_at?->format('M d, Y h:i A'),
        ])->values()->all();

        $dashboardRoute = new DashboardRoute();
        $branding = ['app_name' => config('app.name', 'Laravel')];
        $user = $request->user();

        return view('dashboard.myleaverequests', compact('leaveRequests', 'leaveTypes', 'employee', 'stats', 'tab', 'leaveBalance', 'leaveRequestDetails', 'dashboardRoute', 'branding', 'user'));
    }

    public function storeForSelf(Request $request)
    {
        $user = $request->user();
        $employee = $user?->employee;

        if (! $user || ! $employee) {
            return back()->withErrors(['employee_id' => 'Your employee profile is not linked yet. Please contact HR.'])->withInput();
        }

        $validated = $request->validate([
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:2000',
        ]);

        $validated['user_id'] = $user->id;
        $validated['employee_id'] = $employee->id;
        $validated['status'] = 'pending';

        LeaveRequest::create($validated);

        return back()->with('success', 'Your leave request has been submitted successfully.');
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
