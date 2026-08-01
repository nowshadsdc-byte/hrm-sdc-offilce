@extends('tyro-dashboard::layouts.user')

@section('title', 'My Attendances')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>My Attendances</span>
@endsection

@section('content')
@php
    $linkedinAccount = $employee?->user ? $employee->user->socialAccounts->firstWhere('provider', 'linkedin') : null;

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

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">My Attendances</h1>
            <p class="page-description">Your linked employee details and attendance history.</p>
        </div>
    </div>
</div>

@if (! $employee)
    <div class="card">
        <div class="card-body">
            <p>No employee is linked to your account yet.</p>
        </div>
    </div>
@else
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
                    <p>{{ $employee->shift?->name ?? '—' }}{{ $employee->shift ? ' (' . $employee->shift->formattedRange() . ')' : '' }}</p>
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

    <div class="card" style="margin-bottom: 1rem; padding: 0.25rem;">
        <div style="display: flex; gap: 0.25rem; flex-wrap: nowrap; overflow-x: auto;">
            <button type="button" class="btn btn-primary" data-profile-tab="attendance" onclick="switchProfileTab('attendance')" style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.875rem; white-space: nowrap;">
                Attendance History
            </button>
            <button type="button" class="btn btn-ghost" data-profile-tab="leave" onclick="switchProfileTab('leave')" style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.875rem; white-space: nowrap;">
                Leave Report
                @if ($leaveRequests->where('status', 'pending')->count() > 0)
                    <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 1.1rem; height: 1.1rem; padding: 0 0.3rem; border-radius: 999px; background: #f59e0b; color: #fff; font-size: 0.7rem; font-weight: 700;">{{ $leaveRequests->where('status', 'pending')->count() }}</span>
                @endif
            </button>
        </div>
    </div>

    <div class="card" style="margin-bottom: 1rem;">
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard.myattendances') }}" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="margin: 0;">
                    <label for="filter-month" class="form-label">Month</label>
                    <input id="filter-month" type="month" name="month" value="{{ $month }}" class="form-input" data-filter-month>
                </div>

                <div style="display: flex; align-items: flex-end; padding-bottom: 0.6rem; color: #9ca3af; font-size: 0.8rem;">or</div>

                <div class="form-group" style="margin: 0;">
                    <label for="filter-start" class="form-label">Start Date</label>
                    <input id="filter-start" type="date" name="start_date" value="{{ $month ? '' : $startDate }}" class="form-input" data-filter-date>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label for="filter-end" class="form-label">End Date</label>
                    <input id="filter-end" type="date" name="end_date" value="{{ $month ? '' : $endDate }}" class="form-input" data-filter-date>
                </div>

                <button type="submit" class="btn btn-secondary">Filter</button>
                <a href="{{ route('dashboard.myattendances') }}" class="btn btn-secondary">Reset</a>
            </form>
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
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Present Days</div><div class="stat-value">{{ $stats['present'] }}</div></div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Late Days</div><div class="stat-value">{{ $stats['late'] }}</div></div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Early Leave Days</div><div class="stat-value">{{ $stats['early_leave'] }}</div></div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Total Hours</div><div class="stat-value">{{ rtrim(rtrim(number_format($stats['total_hours'], 1, '.', ''), '0'), '.') }}</div></div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Absent Days</div><div class="stat-value">{{ $stats['absent'] }}</div></div>
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
                                            <span style="display: inline-flex; align-items: center; gap: 0.35rem; width: fit-content; padding: 0.15rem 0.55rem; border-radius: 8px; background: {{ $isEarlyLeave ? '#fee2f2' : '#dcfce7' }};">
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
    </div>

    <div id="profile-tab-leave" style="display: none;">
        <div class="page-header" style="margin-bottom: 1rem;">
            <div class="page-header-row">
                <div>
                    <h2 class="page-title" style="font-size: 1.25rem;">Leave Report</h2>
                    <p class="page-description" style="font-size: 0.9rem;">{{ $leaveBalance['year'] ?? 'Yearly' }} leave balance and request history.</p>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Total Allocation</div><div class="stat-value">{{ $leaveBalance['total'] }}</div></div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Used (Approved)</div><div class="stat-value">{{ $leaveBalance['approved'] }}</div></div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Pending</div><div class="stat-value">{{ $leaveBalance['pending'] }}</div></div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Available</div><div class="stat-value">{{ $leaveBalance['available'] }}</div></div>
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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #6b7280; padding: 1.5rem;">No leave requests yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
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
</script>
@endpush

@endsection
