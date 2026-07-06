@extends('tyro-dashboard::layouts.admin')

@section('title', 'Attendance')

@section('breadcrumb')
    <a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Attendance</span>
@endsection

@push('styles')
    @vite(['resources/css/app.css'])
@endpush

@section('content')
    <div class="container mx-auto p-6">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="page-title">Daily Attendance</h1>
                <p class="page-description">Monitor check-in/check-out and working hours</p>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary"
                    style="display: inline-flex; align-items: center; gap: 0.4rem;">
                    <svg viewBox="0 0 24 24" style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 10l5 5 5-5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15V3" />
                    </svg>
                    Export CSV
                </button>

                <form method="GET" action="{{ route('dashboard.attendance') }}">
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="hidden" name="export" value="csv">
                        <input type="date" name="date" value="{{ request('date', now()->toDateString()) }}"
                            class="form-input" onchange="this.form.submit()">
                        <button class="btn btn-primary">Sync Now</button>
                    </div>
                </form>

            </div>
        </div>

        <div class="stats-grid">
            @foreach ($kpis as $kpi)
                <div class="stat-card"
                    style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
                    <div class="stat-content" style="flex: 1;">
                        <div class="stat-label" style="font-size: 0.9375rem;">{{ $kpi['label'] }}</div>
                        <div class="stat-value">{{ $kpi['value'] }}</div>
                        <div class="stat-change {{ $kpi['change_class'] }}">
                            {!! $kpi['change_icon'] !!}
                            <span>{{ $kpi['change_text'] }}</span>
                        </div>
                    </div>
                    <div class="stat-icon {{ $kpi['icon_class'] }}" style="margin-bottom: 0; flex-shrink: 0;">
                        {!! $kpi['icon'] !!}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card" style="margin-top: 1rem;">
            <div class="card-header"
                style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <div>
                    <h3 class="card-title">Attendance Records</h3>
                    <p class="muted-text" style="font-size: 0.85rem;">EmployeeName, Check In, Lunch Start, Lunch End, Lunch
                        spend time, Check Out, Hours, Overtime, Late Status, Remarks</p>
                </div>
                <p class="muted-text" style="font-size: 0.85rem; margin: 0;">Total: {{ $attendances->total() }}</p>
            </div>

            <div class="card-body" style="padding-top: 0.75rem;">
                <div style="overflow-x: auto; border: 1px solid var(--border); border-radius: 0.75rem;">
                    <table class="table" style="min-width: 1100px; margin: 0;">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="font-weight: 700; color: #0f172a;">EmployeeName</th>
                                <th style="font-weight: 700; color: #0f172a;">Check In</th>
                                <th style="font-weight: 700; color: #0f172a;">Lunch Start</th>
                                <th style="font-weight: 700; color: #0f172a;">Lunch End</th>
                                <th style="font-weight: 700; color: #0f172a;">Lunch spend time</th>
                                <th style="font-weight: 700; color: #0f172a;">Check Out</th>
                                <th style="font-weight: 700; color: #0f172a;">Hours</th>
                                <th style="font-weight: 700; color: #0f172a;">Overtime</th>
                                <th style="font-weight: 700; color: #0f172a;">Late Status</th>
                                <th style="font-weight: 700; color: #0f172a;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $attendance)
                                @php
                                    $checkIn = $attendance->check_in;
                                    $checkOut = $attendance->check_out;
                                    $hours =
                                        $checkIn && $checkOut
                                            ? round($checkOut->diffInMinutes($checkIn) / 60, 2)
                                            : null;
                                    $overtime = $hours !== null ? max(round($hours - 9, 2), 0) : null;
                                    $lateStatus =
                                        $checkIn && $checkIn->format('H:i:s') > '09:00:00' ? 'Late' : 'On Time';
                                @endphp
                                <tr style="background: {{ $loop->odd ? '#ffffff' : '#fcfcfd' }};">
                                    <td style="font-weight: 600; color: #111827;">
                                        {{ $attendance->employee?->user?->name ?? ($attendance->employee?->name ?? 'N/A') }}
                                    </td>
                                    <td>{{ $checkIn ? $checkIn->format('H:i') : '—' }}</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>{{ $checkOut ? $checkOut->format('H:i') : '—' }}</td>
                                    <td>{{ $hours !== null ? $hours . 'h' : '—' }}</td>
                                    <td>{{ $overtime !== null ? $overtime . 'h' : '—' }}</td>
                                    <td>
                                        <span
                                            style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: {{ $lateStatus === 'Late' ? '#fef3c7' : '#dcfce7' }}; color: {{ $lateStatus === 'Late' ? '#92400e' : '#166534' }};">
                                            {{ $lateStatus }}
                                        </span>
                                    </td>
                                    <td style="color: #6b7280;">{{ $checkIn ? 'Recorded' : 'No record' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" style="text-align: center; color: #6b7280; padding: 1.5rem;">No
                                        attendance records found for selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($attendances, 'links'))
                    <div style="margin-top: 0.85rem;">
                        {{ $attendances->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>


@endsection
