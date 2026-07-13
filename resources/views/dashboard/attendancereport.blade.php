@extends('tyro-dashboard::layouts.admin')

@section('title', 'Attendance Report')

@section('breadcrumb')
    <a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Attendance Report</span>
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-row">
            <div>
                <h1 class="page-title">Attendance Report</h1>
                <p class="page-description" style="font-size: 1rem;">Monthly employee attendance summary — {{ $monthLabel }}.</p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="{{ route('dashboard.devices') }}" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 0.4rem;">
                    <svg viewBox="0 0 24 24" style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline stroke-linecap="round" stroke-linejoin="round" points="7 10 12 15 17 10" />
                        <line stroke-linecap="round" stroke-linejoin="round" x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                    Import Device Data
                </a>
                <a href="{{ route('dashboard.attendancereport.export', ['month' => $month, 'employee_id' => $employeeId, 'status' => $status]) }}"
                    class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.4rem;">
                    <svg viewBox="0 0 24 24" style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 10l5 5 5-5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15V3" />
                    </svg>
                    Export CSV
                </a>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 1rem;">
        <div class="card-body" style="padding-top: 1rem; padding-bottom: 1rem;">
            <form method="GET" action="{{ route('dashboard.attendancereport') }}" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="margin: 0;">
                    <label for="report-month" class="form-label">Month</label>
                    <input id="report-month" type="month" name="month" value="{{ $month }}" class="form-input">
                </div>
                <div class="form-group" style="margin: 0; min-width: 14rem;">
                    <label for="report-employee" class="form-label">Employee</label>
                    <select id="report-employee" name="employee_id" class="form-input">
                        <option value="">All Employees</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" {{ $employeeId === $emp->id ? 'selected' : '' }}>
                                {{ $emp->user?->name ?? $emp->name ?? 'Employee #'.$emp->id }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin: 0; min-width: 10rem;">
                    <label for="report-status" class="form-label">Status</label>
                    <select id="report-status" name="status" class="form-input">
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="separated" {{ $status === 'separated' ? 'selected' : '' }}>Separated</option>
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                @if ($employee)
                    <a href="{{ route('dashboard.attendancereport', ['month' => $month, 'status' => $status]) }}" class="btn btn-secondary">Clear Employee</a>
                @endif
            </form>
        </div>
    </div>

    @if ($employee)
        <div class="stats-grid">
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Present</div><div class="stat-value">{{ $kpis['present'] }}</div></div>
                <div class="stat-icon stat-icon-success" style="margin-bottom: 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
                </div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Late</div><div class="stat-value">{{ $kpis['late'] }}</div></div>
                <div class="stat-icon stat-icon-warning" style="margin-bottom: 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Absent</div><div class="stat-value">{{ $kpis['absent'] }}</div></div>
                <div class="stat-icon stat-icon-danger" style="margin-bottom: 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/></svg>
                </div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Weekend</div><div class="stat-value">{{ $kpis['weekend'] }}</div></div>
                <div class="stat-icon stat-icon-info" style="margin-bottom: 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Holiday</div><div class="stat-value">{{ $kpis['holiday'] }}</div></div>
                <div class="stat-icon stat-icon-info" style="margin-bottom: 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Total Hours</div><div class="stat-value">{{ $kpis['hours'] }}</div></div>
                <div class="stat-icon stat-icon-success" style="margin-bottom: 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 1rem;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <div>
                    <h3 class="card-title">{{ $employee->user?->name ?? $employee->name }}</h3>
                    <p class="muted-text" style="font-size: 0.85rem;">{{ $employee->department ?? 'No department' }} · Daily attendance for {{ $monthLabel }}</p>
                </div>
            </div>
            <div class="card-body" style="padding-top: 0.75rem;">
                <div style="overflow-x: auto; border: 1px solid var(--border); border-radius: 0.75rem;">
                    <table class="table" style="min-width: 900px; margin: 0;">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="font-weight: 700; color: #0f172a;">Date</th>
                                <th style="font-weight: 700; color: #0f172a;">Day</th>
                                <th style="font-weight: 700; color: #0f172a;">Status</th>
                                <th style="font-weight: 700; color: #0f172a;">Check In</th>
                                <th style="font-weight: 700; color: #0f172a;">Check Out</th>
                                <th style="font-weight: 700; color: #0f172a;">Hours</th>
                                <th style="font-weight: 700; color: #0f172a;">Overtime</th>
                                <th style="font-weight: 700; color: #0f172a;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $rowColors = [
                                    'Late' => '#fffbeb',
                                    'Absent' => '#fef2f2',
                                    'Weekend' => '#f8fafc',
                                    'Holiday' => '#eff6ff',
                                    'Upcoming' => '#ffffff',
                                ];
                                $badgeColors = [
                                    'Present' => ['#dcfce7', '#166534'],
                                    'Late' => ['#fef3c7', '#92400e'],
                                    'Absent' => ['#fee2e2', '#991b1b'],
                                    'Weekend' => ['#e2e8f0', '#475569'],
                                    'Holiday' => ['#dbeafe', '#1e40af'],
                                    'Upcoming' => ['#f1f5f9', '#94a3b8'],
                                ];
                            @endphp
                            @foreach ($days as $day)
                                @php
                                    [$badgeBg, $badgeColor] = $badgeColors[$day['status']] ?? ['#f1f5f9', '#94a3b8'];
                                @endphp
                                <tr style="background: {{ $rowColors[$day['status']] ?? '#ffffff' }};">
                                    <td style="font-weight: 600; color: #111827;">{{ $day['date']->format('M d, Y') }}</td>
                                    <td>{{ $day['date']->format('l') }}</td>
                                    <td>
                                        <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: {{ $badgeBg }}; color: {{ $badgeColor }};">
                                            {{ $day['status'] === 'Holiday' && $day['holiday_name'] ? $day['holiday_name'] : $day['status'] }}
                                        </span>
                                    </td>
                                    <td>{{ $day['check_in'] ?? '—' }}</td>
                                    <td>{{ $day['check_out'] ?? '—' }}</td>
                                    <td>{{ $day['hours'] ?? '—' }}</td>
                                    <td>{{ $day['overtime'] ?? '—' }}</td>
                                    <td style="color: #6b7280;">{{ $day['remarks'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="stats-grid">
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Total Employees</div><div class="stat-value">{{ $kpis['total_employees'] }}</div></div>
                <div class="stat-icon stat-icon-info" style="margin-bottom: 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Working Days</div><div class="stat-value">{{ $kpis['working_days'] }}</div></div>
                <div class="stat-icon stat-icon-info" style="margin-bottom: 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Total Present</div><div class="stat-value">{{ $kpis['total_present'] }}</div></div>
                <div class="stat-icon stat-icon-success" style="margin-bottom: 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
                </div>
            </div>
            <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                <div class="stat-content"><div class="stat-label">Total Late</div><div class="stat-value">{{ $kpis['total_late'] }}</div></div>
                <div class="stat-icon stat-icon-warning" style="margin-bottom: 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 1rem;">
            <div class="card-header">
                <h3 class="card-title">Employee Summary — {{ $monthLabel }}</h3>
                <p class="muted-text" style="font-size: 0.85rem;">Select an employee above to view their daily attendance breakdown.</p>
            </div>
            <div class="card-body" style="padding-top: 0.75rem;">
                <div style="overflow-x: auto; border: 1px solid var(--border); border-radius: 0.75rem;">
                    <table class="table" style="min-width: 800px; margin: 0;">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="font-weight: 700; color: #0f172a;">Employee</th>
                                <th style="font-weight: 700; color: #0f172a;">Department</th>
                                <th style="font-weight: 700; color: #0f172a;">Present</th>
                                <th style="font-weight: 700; color: #0f172a;">Late</th>
                                <th style="font-weight: 700; color: #0f172a;">Absent</th>
                                <th style="font-weight: 700; color: #0f172a;">Total Hours</th>
                                <th style="font-weight: 700; color: #0f172a;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($summaries as $row)
                                <tr style="background: {{ $row['late'] > 0 ? '#fffbeb' : '#ffffff' }};">
                                    <td style="font-weight: 600; color: #111827;">{{ $row['name'] }}</td>
                                    <td>{{ $row['department'] ?? '—' }}</td>
                                    <td>{{ $row['present'] }}</td>
                                    <td>
                                        @if ($row['late'] > 0)
                                            <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: #fef3c7; color: #92400e;">{{ $row['late'] }}</span>
                                        @else
                                            {{ $row['late'] }}
                                        @endif
                                    </td>
                                    <td>{{ $row['absent'] }}</td>
                                    <td>{{ $row['hours'] }}</td>
                                    <td>
                                        <a href="{{ route('dashboard.attendancereport', ['month' => $month, 'employee_id' => $row['id'], 'status' => $status]) }}" class="btn btn-secondary btn-sm">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #6b7280; padding: 1.5rem;">No employees found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
