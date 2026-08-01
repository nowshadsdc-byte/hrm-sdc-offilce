@extends('tyro-dashboard::layouts.admin')

@section('title', 'Employee Details')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('employees.index') }}">Employees</a>
<span class="breadcrumb-separator">/</span>
<span>{{ $employee->name }}</span>
@endsection

@php
    $formatAttendanceTime = function ($value) {
        if ($value === null) {
            return null;
        }

        $carbon = $value instanceof \Carbon\Carbon ? $value : \Carbon\Carbon::parse($value);

        return [
            'time' => $carbon->format('h:i'),
            'period' => $carbon->format('A'),
        ];
    };
@endphp

@section('content')
@php
    $linkedinAccount = $employee->user ? $employee->user->socialAccounts->firstWhere('provider', 'linkedin') : null;
@endphp
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">{{ $employee->name }}</h1>
            <p class="page-description">Employee details and attendance history.</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Back to Employees</a>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body">
        <div class="detail-grid">
            <div>
                <h3 class="detail-label">Name</h3>
                <p>{{ $employee->name }}</p>
            </div>
            <div>
                <h3 class="detail-label">NID</h3>
                <p>{{ $employee->nid ?? '—' }}</p>
            </div>
            <div>
                <h3 class="detail-label">Phone</h3>
                <p>{{ $employee->phone ?? '—' }}</p>
            </div>
            <div>
                <h3 class="detail-label">Device User ID</h3>
                <p>{{ $employee->device_user_id ?? '—' }}</p>
            </div>
            <div>
                <h3 class="detail-label">Device Card No</h3>
                <p>{{ $employee->device_cardno ?? '—' }}</p>
            </div>
            <div>
                <h3 class="detail-label">Department</h3>
                <p>{{ $employee->department ?? '—' }}</p>
            </div>
            <div>
                <h3 class="detail-label">Designation</h3>
                <p>{{ $employee->designation ?? '—' }}</p>
            </div>
            <div>
                <h3 class="detail-label">Job Title</h3>
                <p>{{ $employee->job_title ?? '—' }}</p>
            </div>
            <div>
                <h3 class="detail-label">Role</h3>
                <p>{{ $employee->role ?? '—' }}</p>
            </div>
            <div>
                <h3 class="detail-label">Linked User</h3>
                <p>{{ $employee->user?->name ?? 'No linked user account' }}</p>
            </div>
            <div>
                <h3 class="detail-label">Email Address</h3>
                <p>{{ $employee->user?->email ?? '—' }}</p>
            </div>
            <div>
                <h3 class="detail-label">Account Status</h3>
                <p>
                    @if ($employee->user)
                        @if ($employee->user->email_verified_at)
                            Verified
                        @else
                            Unverified
                        @endif
                    @else
                        No linked user account
                    @endif
                </p>
            </div>
            @if ($linkedinAccount)
                <div>
                    <h3 class="detail-label">LinkedIn Status</h3>
                    <p>
                        {{ $employee->user->name }} is linked via LinkedIn
                        @if ($linkedinAccount->provider_email)
                            — {{ $linkedinAccount->provider_email }}
                        @endif
                    </p>
                </div>
            @endif
            <div>
                <h3 class="detail-label">Shift</h3>
                <p>{{ $employee->shift?->name ?? '—' }}{{ $employee->shift ? ' ('.$employee->shift->formattedRange().')' : '' }}</p>
            </div>
            <div>
                <h3 class="detail-label">Annual Leave Days</h3>
                <p>
                    {{ $leaveBalance['total'] }} days/year
                    @if ($employee->annual_leave_days !== null)
                        <span style="font-size: 0.75rem; color: #6b7280;">(custom)</span>
                    @else
                        <span style="font-size: 0.75rem; color: #6b7280;">(default)</span>
                    @endif
                </p>
            </div>
            <div>
                <h3 class="detail-label">Join Date</h3>
                <p>{{ optional($employee->job_join_date)->format('M d, Y') ?? '—' }}</p>
            </div>
            <div>
                <h3 class="detail-label">Status</h3>
                <p>{{ ucwords(str_replace('_', ' ', $employee->status)) }}</p>
            </div>
            <div class="detail-full-width">
                <h3 class="detail-label">Address</h3>
                <p>{{ $employee->address ?? '—' }}</p>
            </div>
            <div class="detail-full-width">
                <h3 class="detail-label">Notes</h3>
                <p>{{ $employee->transfer_promotion_notes ?? '—' }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Tab Navigation --}}
<div class="card" style="margin-bottom: 1rem; padding: 0.25rem;">
    <div style="display: flex; gap: 0.25rem; flex-wrap: nowrap; overflow-x: auto;">
        <button type="button" class="btn btn-primary" data-profile-tab="attendance" onclick="switchProfileTab('attendance')" style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.875rem; white-space: nowrap;">
            <svg viewBox="0 0 24 24" style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" stroke-width="1.8">
                <rect x="3" y="4" width="18" height="17" rx="2" />
                <path d="M3 9h18M8 3v3M16 3v3" stroke-linecap="round" />
            </svg>
            Attendance History
        </button>
        <button type="button" class="btn btn-ghost" data-profile-tab="leave" onclick="switchProfileTab('leave')" style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.875rem; white-space: nowrap;">
            <svg viewBox="0 0 24 24" style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M7 13c2 0 2-2 4-2s2 2 4 2 2-2 4-2" stroke-linecap="round" />
                <path d="M4 18c2 0 2-2 4-2s2 2 4 2 2-2 4-2 2 2 4 2" stroke-linecap="round" />
                <path d="M5 8c1.5-2.5 5-3.5 7-1 2-2.5 5.5-1.5 7 1" stroke-linecap="round" />
            </svg>
            Leave Report
            @if ($leaveRequests->where('status', 'pending')->count() > 0)
                <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 1.1rem; height: 1.1rem; padding: 0 0.3rem; border-radius: 999px; background: #f59e0b; color: #fff; font-size: 0.7rem; font-weight: 700;">{{ $leaveRequests->where('status', 'pending')->count() }}</span>
            @endif
        </button>
    </div>
</div>

<div id="profile-tab-attendance">

<div class="page-header" style="margin-bottom: 1rem;">
    <div class="page-header-row">
        <div>
            <h2 class="page-title" style="font-size: 1.25rem;">Attendance History</h2>
            <p class="page-description" style="font-size: 0.9rem;">
                {{ \Carbon\Carbon::parse($startDate)->format('M j, Y') }} &ndash; {{ \Carbon\Carbon::parse($endDate)->format('M j, Y') }}
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a
                href="{{ route('attendances.export', ['employee_id' => $employee->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                class="btn btn-secondary"
                style="display: inline-flex; align-items: center; gap: 0.4rem;"
            >
                <svg viewBox="0 0 24 24" style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 10l5 5 5-5" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15V3" />
                </svg>
                Download CSV
            </a>

            @if ($employee->device_user_id)
                <form method="POST" action="{{ route('employees.sync-attendance', $employee) }}">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month }}">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    <button type="submit" class="btn" style="display: inline-flex; align-items: center; gap: 0.4rem; background: #4338ca; color: #fff; border: none; padding: 0.55rem 1rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">
                        <svg viewBox="0 0 24 24" style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Sync from Device
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
        <div class="stat-content"><div class="stat-label">Present Days</div><div class="stat-value">{{ $stats['present'] }}</div></div>
        <div class="stat-icon stat-icon-success" style="margin-bottom: 0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
        </div>
    </div>
    <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
        <div class="stat-content"><div class="stat-label">Late Days</div><div class="stat-value">{{ $stats['late'] }}</div></div>
        <div class="stat-icon stat-icon-warning" style="margin-bottom: 0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
    </div>
    <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
        <div class="stat-content"><div class="stat-label">Early Leave Days</div><div class="stat-value">{{ $stats['early_leave'] }}</div></div>
        <div class="stat-icon stat-icon-warning" style="margin-bottom: 0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 17l5-5-5-5M6 17l5-5-5-5"/></svg>
        </div>
    </div>
    <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
        <div class="stat-content"><div class="stat-label">Total Hours</div><div class="stat-value">{{ rtrim(rtrim(number_format($stats['total_hours'], 1, '.', ''), '0'), '.') }}</div></div>
        <div class="stat-icon stat-icon-success" style="margin-bottom: 0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
    </div>
    <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
        <div class="stat-content"><div class="stat-label">Absent Days</div><div class="stat-value">{{ $stats['absent'] }}</div></div>
        <div class="stat-icon stat-icon-danger" style="margin-bottom: 0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/></svg>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 1rem; margin-bottom: 1rem;">
    <div class="card-body" style="padding-top: 1rem; padding-bottom: 1rem;">
        <form method="GET" action="{{ route('employees.show', $employee) }}" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin: 0;">
                <label for="filter-month" class="form-label">Month</label>
                <input id="filter-month" type="month" name="month" value="{{ $month }}" class="form-input border border-gray-300 rounded-md px-3 py-2" data-filter-month>
            </div>

            <div style="display: flex; align-items: flex-end; padding-bottom: 0.6rem; color: #9ca3af; font-size: 0.8rem;">or</div>

            <div class="form-group" style="margin: 0;">
                <label for="filter-start" class="form-label">Start Date</label>
                <input id="filter-start" type="date" name="start_date" value="{{ $month ? '' : $startDate }}" class="form-input border border-gray-300 rounded-md px-3 py-2" data-filter-date>
            </div>

            <div class="form-group" style="margin: 0;">
                <label for="filter-end" class="form-label">End Date</label>
                <input id="filter-end" type="date" name="end_date" value="{{ $month ? '' : $endDate }}" class="form-input border border-gray-300 rounded-md px-3 py-2" data-filter-date>
            </div>

            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<div class="card" style="border-radius: 0.75rem; background: #ffffff; border: 1px solid var(--border);">
    <div class="card-header">
        <h3 class="card-title">Daily Punches</h3>
    </div>
    <div class="card-body" style="padding-top: 0.75rem;">
        <div style="overflow-x: auto; border: 1px solid var(--border); border-radius: 0.75rem;">
            <table class="table" style="min-width: 700px; margin: 0;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="font-weight: 700; color: #0f172a;">Date</th>
                        <th style="font-weight: 700; color: #0f172a;">Check In</th>
                        <th style="font-weight: 700; color: #0f172a;">Check Out</th>
                        <th style="font-weight: 700; color: #0f172a;">Hours</th>
                        <th style="font-weight: 700; color: #0f172a;">Status</th>
                        <th style="font-weight: 700; color: #0f172a;">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $row)
                        @php
                            $attendance = $row->attendance;
                            $isLate = $attendance ? (bool) $attendance->late_status : false;
                            $isEarlyLeave = $attendance ? (bool) $attendance->early_leave_status : false;
                            $checkIn = $attendance ? $formatAttendanceTime($attendance->check_in_utc) : null;
                            $checkOut = $attendance ? $formatAttendanceTime($attendance->check_out_utc) : null;
                            $rowBg = match (true) {
                                $row->status === 'absent' => '#fff7ed',
                                $row->status === 'weekend' => '#f8fafc',
                                $isLate || $isEarlyLeave => '#fef2f2',
                                default => '#ffffff',
                            };
                        @endphp
                        <tr style="background: {{ $rowBg }};">
                            <td style="font-weight: 600; color: #111827; white-space: nowrap;">{{ $row->date->format('D, M j, Y') }}</td>
                            <td style="font-variant-numeric: tabular-nums;">
                                @if ($checkIn)
                                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; width: fit-content; padding: 0.15rem 0.55rem; border-radius: 8px; background: {{ $isLate ? '#fee2e2' : '#dcfce7' }};">
                                        <span style="font-weight: 700; color: {{ $isLate ? '#b91c1c' : '#166534' }};">{{ $checkIn['time'] }}</span>
                                        <span style="font-size: 0.7rem; font-weight: 600; color: {{ $isLate ? '#b91c1c' : '#166534' }};">{{ $checkIn['period'] }}</span>
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="font-variant-numeric: tabular-nums;">
                                @if ($checkOut)
                                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; width: fit-content; padding: 0.15rem 0.55rem; border-radius: 8px; background: {{ $isEarlyLeave ? '#fee2e2' : '#dcfce7' }};">
                                        <span style="font-weight: 700; color: {{ $isEarlyLeave ? '#b91c1c' : '#166534' }};">{{ $checkOut['time'] }}</span>
                                        <span style="font-size: 0.7rem; font-weight: 600; color: {{ $isEarlyLeave ? '#b91c1c' : '#166534' }};">{{ $checkOut['period'] }}</span>
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $attendance && $attendance->total_work_minutes !== null ? rtrim(rtrim(number_format($attendance->total_work_minutes / 60, 2, '.', ''), '0'), '.').'h' : '—' }}</td>
                            <td>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                    @if ($row->status === 'weekend')
                                        <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: #e2e8f0; color: #475569;">Weekend</span>
                                    @elseif ($row->status === 'absent')
                                        <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: #ffedd5; color: #9a3412;">Absent</span>
                                    @else
                                        @if ($isLate)
                                            <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #b91c1c;">Late</span>
                                        @endif
                                        @if ($isEarlyLeave)
                                            <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #b91c1c;">Early Leave</span>
                                        @endif
                                        @if (! $isLate && ! $isEarlyLeave)
                                            <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: #dcfce7; color: #166534;">On Time</span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td style="color: #6b7280;">
                                {{ $attendance?->remarks ?? ($row->status === 'weekend' ? 'Weekend' : ($row->status === 'absent' ? 'No punch recorded' : '—')) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: #6b7280; padding: 1.5rem;">No attendance records for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($attendances->hasPages())
            <div style="padding: 1rem 0 0;">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>
</div>

</div>{{-- /#profile-tab-attendance --}}

<div id="profile-tab-leave" style="display: none;">
    <div class="page-header" style="margin-bottom: 1rem;">
        <div class="page-header-row">
            <div>
                <h2 class="page-title" style="font-size: 1.25rem;">Leave Report</h2>
                <p class="page-description" style="font-size: 0.9rem;">{{ $leaveBalance['year'] }} leave balance and request history.</p>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="btn btn-primary" onclick="openProfileApplyLeaveModal()" style="display: inline-flex; align-items: center; gap: 0.4rem;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Apply Leave
                </button>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
            <div class="stat-content"><div class="stat-label">Total Allocation</div><div class="stat-value">{{ $leaveBalance['total'] }}</div></div>
            <div class="stat-icon stat-icon-info" style="margin-bottom: 0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 3v3M16 3v3" stroke-linecap="round"/></svg>
            </div>
        </div>
        <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
            <div class="stat-content"><div class="stat-label">Used (Approved)</div><div class="stat-value">{{ $leaveBalance['approved'] }}</div></div>
            <div class="stat-icon stat-icon-success" style="margin-bottom: 0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" fill="currentColor" stroke="none"/></svg>
            </div>
        </div>
        <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
            <div class="stat-content"><div class="stat-label">Pending</div><div class="stat-value">{{ $leaveBalance['pending'] }}</div></div>
            <div class="stat-icon stat-icon-warning" style="margin-bottom: 0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
        </div>
        <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
            <div class="stat-content"><div class="stat-label">Available</div><div class="stat-value">{{ $leaveBalance['available'] }}</div></div>
            <div class="stat-icon stat-icon-success" style="margin-bottom: 0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
        </div>
    </div>

    <div class="card" style="border-radius: 0.75rem; background: #ffffff; border: 1px solid var(--border); margin-top: 1rem;">
        <div class="card-header">
            <h3 class="card-title">Leave Requests</h3>
        </div>
        <div class="card-body" style="padding-top: 0.75rem;">
            <div style="overflow-x: auto; border: 1px solid var(--border); border-radius: 0.75rem;">
                <table class="table" style="min-width: 700px; margin: 0;">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="font-weight: 700; color: #0f172a;">Type</th>
                            <th style="font-weight: 700; color: #0f172a;">Dates</th>
                            <th style="font-weight: 700; color: #0f172a;">Days</th>
                            <th style="font-weight: 700; color: #0f172a;">Status</th>
                            <th style="font-weight: 700; color: #0f172a;">Reason</th>
                            <th style="font-weight: 700; color: #0f172a;">Applied</th>
                            @if ($isAdmin)
                                <th style="font-weight: 700; color: #0f172a; text-align: center;">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leaveRequests as $leaveRequest)
                            <tr>
                                <td style="font-weight: 600; color: #111827;">{{ $leaveRequest->leave_type }}</td>
                                <td style="white-space: nowrap;">{{ $leaveRequest->start_date->format('M j, Y') }} &ndash; {{ $leaveRequest->end_date->format('M j, Y') }}</td>
                                <td>{{ $leaveRequest->days_count }}</td>
                                <td>
                                    @if ($leaveRequest->status === 'pending')
                                        <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: #fef3c7; color: #92400e;">Pending</span>
                                    @elseif ($leaveRequest->status === 'approved')
                                        <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: #dcfce7; color: #166534;">Approved</span>
                                    @else
                                        <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #b91c1c;">Rejected</span>
                                    @endif
                                </td>
                                <td style="color: #6b7280; max-width: 16rem;">{{ $leaveRequest->reason ?? '—' }}</td>
                                <td style="color: #6b7280; white-space: nowrap;">{{ $leaveRequest->created_at->format('M j, Y') }}</td>
                                @if ($isAdmin)
                                    <td style="text-align: center;">
                                        @if ($leaveRequest->status === 'pending')
                                            <div style="display: flex; gap: 0.35rem; justify-content: center;">
                                                <button type="button" class="btn btn-success btn-sm" onclick="openProfileApproveModal({{ $leaveRequest->id }})">Approve</button>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="openProfileRejectModal({{ $leaveRequest->id }})">Reject</button>
                                            </div>
                                        @else
                                            <button type="button" class="btn btn-secondary btn-sm" style="opacity: 0.6;" onclick="confirmProfileDeleteLeave({{ $leaveRequest->id }})">Delete</button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 7 : 6 }}" style="text-align: center; color: #6b7280; padding: 1.5rem;">No leave requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>{{-- /#profile-tab-leave --}}

{{-- Apply Leave Modal (scoped to this employee) --}}
<div id="profileApplyLeaveModal" style="display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,0.45); align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 1rem; width: 100%; max-width: 32rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); color: #000;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);">
            <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Apply for Leave &mdash; {{ $employee->name }}</h3>
        </div>

        <form method="POST" action="{{ route('leave-requests.store') }}" style="padding: 1.25rem 1.5rem 1.5rem;">
            @csrf
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">

            <div style="margin: 0 0 1rem; padding: 0.75rem 1rem; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 0.5rem; font-size: 0.8125rem; color: #0c4a6e;">
                <strong>{{ $leaveBalance['available'] }}</strong> of <strong>{{ $leaveBalance['total'] }}</strong> day(s) available this year ({{ $leaveBalance['approved'] }} used, {{ $leaveBalance['pending'] }} pending).
            </div>

            <div class="form-group">
                <label for="profileLeaveType" class="form-label">Leave Type</label>
                <select id="profileLeaveType" name="leave_type" class="form-input" required>
                    <option value="">Select leave type</option>
                    @foreach ($leaveTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="profileStartDate" class="form-label">Start Date</label>
                    <input type="date" id="profileStartDate" name="start_date" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="profileEndDate" class="form-label">End Date</label>
                    <input type="date" id="profileEndDate" name="end_date" class="form-input" required>
                </div>
            </div>

            <div class="form-group">
                <label for="profileLeaveReason" class="form-label">Reason</label>
                <textarea id="profileLeaveReason" name="reason" class="form-input" rows="3" placeholder="Optional reason for the leave request"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                <button type="button" onclick="closeProfileApplyLeaveModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>

@if ($isAdmin)
    {{-- Approve Modal --}}
    <div id="profileApproveModal" style="display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,0.45); align-items: center; justify-content: center; padding: 1rem;">
        <div style="background: white; border-radius: 1rem; width: 100%; max-width: 28rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); color: #000;">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);">
                <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Approve Leave Request</h3>
            </div>
            <div style="padding: 1.25rem 1.5rem;">
                <p style="color: var(--muted); margin: 0 0 1rem;">Are you sure you want to approve this leave request?</p>
                <form id="profileApproveForm" method="POST" style="display: none;">
                    @csrf
                    @method('PUT')
                </form>
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" onclick="closeProfileApproveModal()" class="btn btn-secondary">Cancel</button>
                    <button type="button" onclick="document.getElementById('profileApproveForm').submit();" class="btn btn-success">Approve</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div id="profileRejectModal" style="display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,0.45); align-items: center; justify-content: center; padding: 1rem;">
        <div style="background: white; border-radius: 1rem; width: 100%; max-width: 28rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); color: #000;">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);">
                <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Reject Leave Request</h3>
            </div>
            <form id="profileRejectForm" method="POST" style="padding: 1.25rem 1.5rem 1.5rem;">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="profileRejectionReason" class="form-label">Rejection Reason</label>
                    <textarea id="profileRejectionReason" name="rejection_reason" class="form-input" rows="3" placeholder="Explain why this request is being rejected" required></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" onclick="closeProfileRejectModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
@endif

@push('styles')
<style>
.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 1rem;
}
.detail-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--muted-foreground);
    margin-bottom: 0.5rem;
}
.detail-full-width {
    grid-column: 1 / -1;
}
</style>
@endpush

@push('scripts')
<script>
    (function () {
        var monthInput = document.querySelector('[data-filter-month]');
        var dateInputs = document.querySelectorAll('[data-filter-date]');

        if (!monthInput || !dateInputs.length) {
            return;
        }

        monthInput.addEventListener('input', function () {
            if (monthInput.value) {
                dateInputs.forEach(function (input) {
                    input.value = '';
                });
            }
        });

        dateInputs.forEach(function (input) {
            input.addEventListener('input', function () {
                if (input.value) {
                    monthInput.value = '';
                }
            });
        });
    })();

    function switchProfileTab(tab) {
        document.getElementById('profile-tab-attendance').style.display = tab === 'attendance' ? '' : 'none';
        document.getElementById('profile-tab-leave').style.display = tab === 'leave' ? '' : 'none';

        document.querySelectorAll('[data-profile-tab]').forEach(function (btn) {
            var active = btn.getAttribute('data-profile-tab') === tab;
            btn.classList.toggle('btn-primary', active);
            btn.classList.toggle('btn-ghost', !active);
        });
    }

    function openProfileApplyLeaveModal() {
        document.getElementById('profileApplyLeaveModal').style.display = 'flex';
    }

    function closeProfileApplyLeaveModal() {
        document.getElementById('profileApplyLeaveModal').style.display = 'none';
    }

    document.getElementById('profileApplyLeaveModal').addEventListener('click', function (e) {
        if (e.target === this) closeProfileApplyLeaveModal();
    });

    @if ($isAdmin)
        function openProfileApproveModal(leaveRequestId) {
            document.getElementById('profileApproveForm').action = '/leave-requests/' + leaveRequestId + '/approve';
            document.getElementById('profileApproveModal').style.display = 'flex';
        }

        function closeProfileApproveModal() {
            document.getElementById('profileApproveModal').style.display = 'none';
        }

        function openProfileRejectModal(leaveRequestId) {
            document.getElementById('profileRejectForm').action = '/leave-requests/' + leaveRequestId + '/reject';
            document.getElementById('profileRejectModal').style.display = 'flex';
        }

        function closeProfileRejectModal() {
            document.getElementById('profileRejectModal').style.display = 'none';
        }

        function confirmProfileDeleteLeave(leaveRequestId) {
            var proceed = window.confirm('Delete this leave request? This action cannot be undone.');

            if (!proceed) {
                return;
            }

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '/leave-requests/' + leaveRequestId;
            form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        }

        document.getElementById('profileApproveModal').addEventListener('click', function (e) {
            if (e.target === this) closeProfileApproveModal();
        });

        document.getElementById('profileRejectModal').addEventListener('click', function (e) {
            if (e.target === this) closeProfileRejectModal();
        });
    @endif
</script>
@endpush
@endsection
