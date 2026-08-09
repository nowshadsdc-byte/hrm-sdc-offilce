<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSettings;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceSyncService;
use App\Services\SyncEmployeeAttendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Throwable;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (! $user || (! method_exists($user, 'hasPrivilege') && ! method_exists($user, 'hasAnyRole'))) {
                abort(403);
            }

            if ($request->routeIs('dashboard.myattendances')) {
                return $next($request);
            }

            if (! $user->hasPrivilege('employees.access') && ! $user->hasAnyRole(['admin', 'super-admin', 'employee'])) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = Employee::query();

        if ($search = $request->query('search')) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('nid', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        if ($department = $request->query('department')) {
            $query->where('department', $department);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($role = $request->query('role')) {
            $query->where(function ($q) use ($role) {
                $q->where('role', $role)
                    ->orWhereHas('user', function ($q) use ($role) {
                        $q->whereHas('roles', function ($q) use ($role) {
                            $q->where('name', $role);
                        });
                    });
            });
        }

        $employees = $query->with(['user.roles', 'attendances.device'])->orderByDesc('created_at')->paginate(36)->withQueryString();

        $users = User::query()->orderBy('name', 'asc')->get();

        return view('employees.index', compact('employees', 'users'));
    }

    public function create(Request $request)
    {
        $users = User::query()->orderBy('name', 'asc')->get();
        $shifts = Shift::query()->orderBy('start_time')->get();
        $isAdmin = $this->userIsAdmin($request);

        return view('employees.create', [
            'users' => $users,
            'shifts' => $shifts,
            'isAdmin' => $isAdmin,
            'defaultAnnualLeaveDays' => AttendanceSettings::current()->default_annual_leave_days ?? 20,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateEmployee($request);
        $validated['clearance_completed'] = $request->boolean('clearance_completed');
        $validated['shift_id'] = $validated['shift_id'] ?? Shift::default()?->id;

        if (! $this->userIsAdmin($request)) {
            unset($validated['annual_leave_days']);
        }

        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function show(Request $request, Employee $employee, AttendanceSyncService $attendanceSyncService)
    {
        $employee->load(['user.socialAccounts', 'shift']);

        [$start, $end, $month] = $this->resolveAttendanceRange($request);

        $baseQuery = fn () => Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);

        // Raw device punches may already exist locally (from an earlier device
        // pull) without having been turned into attendance rows yet - e.g. if
        // this date range was never viewed on the main attendance dashboard.
        // Process them now instead of showing an empty table.
        if ($baseQuery()->doesntExist() && $employee->device_user_id) {
            foreach (CarbonPeriod::create($start, $end) as $day) {
                $attendanceSyncService->syncForDate($day);
            }
        }

        $dailyRows = $this->buildDailyPunchRows($employee, $baseQuery(), $start, $end);

        $stats = [
            'present' => $baseQuery()->whereNotNull('check_in')->count(),
            'late' => $baseQuery()->where('late_status', true)->count(),
            'early_leave' => $baseQuery()->where('early_leave_status', true)->count(),
            'total_hours' => round($baseQuery()->sum('total_work_minutes') / 60, 1),
            'absent' => $dailyRows->where('status', 'absent')->count(),
        ];

        $perPage = 31;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $attendances = new LengthAwarePaginator(
            $dailyRows->forPage($currentPage, $perPage)->values(),
            $dailyRows->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $leaveRequests = $employee->leaveRequests()
            ->with('approver')
            ->orderByDesc('start_date')
            ->get();

        $isAdmin = $this->userIsAdmin($request);
        $user = $request->user();
        $canManageDocuments = $isAdmin || ($user?->employee && $user->employee->id === $employee->id);

        $documents = $canManageDocuments
            ? $employee->documents()->with('uploadedBy')->orderByDesc('created_at')->get()
            : collect();

        return view('employees.show', [
            'employee' => $employee,
            'attendances' => $attendances,
            'stats' => $stats,
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'month' => $month,
            'leaveRequests' => $leaveRequests,
            'leaveBalance' => $employee->leaveBalance(),
            'leaveTypes' => ['Casual Leave', 'Sick Leave', 'Annual Leave', 'Maternity Leave', 'Paternity Leave'],
            'isAdmin' => $isAdmin,
            'canManageDocuments' => $canManageDocuments,
            'documents' => $documents,
        ]);
    }

    /**
     * Pull this employee's punches directly from the configured devices
     * (using the per-user device endpoint) and refresh their attendance
     * rows for the dates currently in view.
     */
    public function syncAttendance(Request $request, Employee $employee, SyncEmployeeAttendance $syncEmployeeAttendance, AttendanceSyncService $attendanceSyncService)
    {
        if (! $employee->device_user_id) {
            return back()->with('error', 'This employee has no Device User ID mapped, so attendance cannot be pulled from a device.');
        }

        [$start, $end] = $this->resolveAttendanceRange($request);

        $devices = Device::all();
        $pulled = 0;
        $reached = false;
        $failures = [];

        foreach ($devices as $device) {
            $result = $syncEmployeeAttendance->sync($device, $employee);

            if ($result['ok']) {
                $reached = true;
                $pulled += $result['inserted'];
            } else {
                $failures[] = $result['message'];
            }
        }

        // Even if every device is unreachable, still reprocess whatever raw
        // punches are already stored locally for this range - a device pull
        // failing shouldn't block attendance rows we already have data for.
        foreach (CarbonPeriod::create($start, $end) as $day) {
            $attendanceSyncService->syncForDate($day);
        }

        if (! $reached) {
            $message = 'Could not reach any configured device to pull fresh attendance. Refreshed attendance from previously stored data instead.';

            if ($failures !== []) {
                $message .= ' '.implode(' ', $failures);
            }

            return redirect()
                ->route('employees.show', array_merge(['employee' => $employee->id], $request->only(['month', 'start_date', 'end_date'])))
                ->with('error', $message);
        }

        $successMessage = "Pulled {$pulled} new punch record(s) from the device and refreshed attendance.";

        if ($failures !== []) {
            $successMessage .= ' '.implode(' ', $failures);
        }

        return redirect()
            ->route('employees.show', array_merge(['employee' => $employee->id], $request->only(['month', 'start_date', 'end_date'])))
            ->with('success', $successMessage);
    }

    /**
     * Build one row per calendar day in the range (not just days with a
     * punch), classified as present / weekend / absent so the Daily Punches
     * table can show absences instead of silently omitting them. Weekend
     * days are read from the Attendance Settings table. Days in the future,
     * or before the employee's join date, are excluded entirely.
     *
     * @return Collection<int, object{date:Carbon,attendance:?Attendance,status:string}>
     */
    protected function buildDailyPunchRows(Employee $employee, $attendanceQuery, Carbon $start, Carbon $end): Collection
    {
        $attendanceByDate = $attendanceQuery->get()->keyBy(fn (Attendance $a) => $a->date->toDateString());

        $weekendDays = collect(AttendanceSettings::current()->weekend_days ?? ['fri'])
            ->map(fn ($day) => strtolower((string) $day))
            ->all();

        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd = $end->copy()->startOfDay();

        // Use Carbon::now()/Carbon::parse() explicitly rather than the now()
        // helper or the model's cast date: this app configures Date::use()
        // with CarbonImmutable globally, and mixing that into a variable
        // typed/reused as Carbon\Carbon further down throws a TypeError.
        $today = Carbon::now()->startOfDay();
        if ($rangeEnd->gt($today)) {
            $rangeEnd = $today->copy();
        }

        if ($employee->job_join_date) {
            $joinDate = Carbon::parse($employee->job_join_date->toDateString());

            if ($joinDate->gt($rangeStart)) {
                $rangeStart = $joinDate;
            }
        }

        if ($rangeStart->gt($rangeEnd)) {
            return collect();
        }

        $rows = collect();

        foreach (CarbonPeriod::create($rangeStart, $rangeEnd) as $day) {
            $attendance = $attendanceByDate->get($day->toDateString());
            $isWeekend = in_array(strtolower($day->format('D')), $weekendDays, true);

            $rows->push((object) [
                'date' => $day->copy(),
                'attendance' => $attendance,
                'status' => $attendance !== null ? 'present' : ($isWeekend ? 'weekend' : 'absent'),
            ]);
        }

        return $rows->sortByDesc('date')->values();
    }

    /**
     * @return array{0:Carbon,1:Carbon,2:?string}
     */
    protected function resolveAttendanceRange(Request $request): array
    {
        $month = $request->query('month');

        if ($month) {
            try {
                $start = Carbon::parse($month.'-01')->startOfMonth();

                return [$start->copy()->startOfDay(), $start->copy()->endOfMonth()->endOfDay(), $start->format('Y-m')];
            } catch (Throwable) {
                // Fall through to the start/end date handling below.
            }
        }

        $start = $request->filled('start_date') ? Carbon::parse($request->query('start_date')) : Carbon::now()->startOfMonth();
        $end = $request->filled('end_date') ? Carbon::parse($request->query('end_date')) : Carbon::now()->endOfMonth();

        if ($end->lt($start)) {
            $end = $start->copy();
        }

        return [$start->startOfDay(), $end->endOfDay(), null];
    }

    public function edit(Request $request, Employee $employee)
    {
        $users = User::query()->orderBy('name', 'asc')->get();
        $shifts = Shift::query()->orderBy('start_time')->get();

        $isAdmin = $this->userIsAdmin($request);
        $user = $request->user();

        return view('employees.edit', [
            'employee' => $employee,
            'users' => $users,
            'shifts' => $shifts,
            'isAdmin' => $isAdmin,
            'canManageDocuments' => $isAdmin || ($user?->employee && $user->employee->id === $employee->id),
            'defaultAnnualLeaveDays' => AttendanceSettings::current()->default_annual_leave_days ?? 20,
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $this->validateEmployee($request);
        $validated['clearance_completed'] = $request->boolean('clearance_completed');
        $validated['shift_id'] = $validated['shift_id'] ?? Shift::default()?->id;

        if (! $this->userIsAdmin($request)) {
            unset($validated['annual_leave_days']);
        }

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    protected function userIsAdmin(Request $request): bool
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'hasAnyRole')) {
            return false;
        }

        return $user->hasAnyRole(config('tyro-dashboard.admin_roles', ['admin', 'super-admin']));
    }

    public function destroy(Employee $employee)
    {
        Employee::destroy($employee->getKey());

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }

    public function myAttendances(Request $request, AttendanceSyncService $attendanceSyncService)
    {

        $user = $request->user();
        $employee = $user->employee;
        if (! $employee) {
            return view('dashboard.myattendances', [
                'employee' => null,
            ]);
        }
        $employee->load(['user.socialAccounts', 'shift']);
        [$start, $end, $month] = $this->resolveAttendanceRange($request);
        $baseQuery = fn () => Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);

        if ($baseQuery()->doesntExist() && $employee->device_user_id) {
            foreach (CarbonPeriod::create($start, $end) as $day) {
                $attendanceSyncService->syncForDate($day);
            }
        }

        $dailyRows = $this->buildDailyPunchRows($employee, $baseQuery(), $start, $end);

        $stats = [
            'present' => $baseQuery()->whereNotNull('check_in')->count(),
            'late' => $baseQuery()->where('late_status', true)->count(),
            'early_leave' => $baseQuery()->where('early_leave_status', true)->count(),
            'total_hours' => round($baseQuery()->sum('total_work_minutes') / 60, 1),
            'absent' => $dailyRows->where('status', 'absent')->count(),
        ];

        $perPage = 31;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $attendances = new LengthAwarePaginator(
            $dailyRows->forPage($currentPage, $perPage)->values(),
            $dailyRows->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $leaveRequests = $employee->leaveRequests()
            ->with('approver')
            ->orderByDesc('start_date')
            ->get();

        return view('dashboard.myattendances', [
            'employee' => $employee,
            'attendances' => $attendances,
            'stats' => $stats,
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'month' => $month,
            'leaveRequests' => $leaveRequests,
            'leaveBalance' => $employee->leaveBalance(),
            'leaveTypes' => ['Casual Leave', 'Sick Leave', 'Annual Leave', 'Maternity Leave', 'Paternity Leave'],
            'isAdmin' => $this->userIsAdmin($request),
        ]);
    }

    protected function validateEmployee(Request $request): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'device_user_id' => ['nullable', 'string', 'max:255'],
            'device_cardno' => ['nullable', 'string', 'max:255'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'annual_leave_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'nid' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'job_join_date' => ['nullable', 'date'],
            'job_description' => ['nullable', 'string'],
            'nid_file' => ['nullable', 'string', 'max:255'],
            'certificate_file' => ['nullable', 'string', 'max:255'],
            'contract_file' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:active,inactive,separated'],
            'transfer_promotion_notes' => ['nullable', 'string'],
            'separation_type' => ['nullable', 'string', 'max:255'],
            'separation_date' => ['nullable', 'date'],
            'clearance_completed' => ['required', 'boolean'],
        ]);
    }
}
