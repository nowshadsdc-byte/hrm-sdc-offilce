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

@if ($syncResult)
    <div class="card" style="padding: 1rem 1.5rem; border-radius: 0.75rem; background: #ecfdf5; border: 1px solid #a7f3d0; margin-bottom: 1rem;">
        <p style="margin: 0; color: #065f46;">{{ $syncResult['message'] ?? 'Sync completed for '.$date.'.' }}</p>
    </div>
@endif

<div class="stats-grid">
    <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
        <div class="stat-content"><div class="stat-label">Marked</div><div class="stat-value">{{ $stats['total'] }}</div></div>
        <div class="stat-icon stat-icon-info" style="margin-bottom: 0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
        </div>
    </div>
    <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
        <div class="stat-content"><div class="stat-label">Present</div><div class="stat-value">{{ $stats['present'] }}</div></div>
        <div class="stat-icon stat-icon-success" style="margin-bottom: 0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
        </div>
    </div>
    <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
        <div class="stat-content"><div class="stat-label">Late</div><div class="stat-value">{{ $stats['late'] }}</div></div>
        <div class="stat-icon stat-icon-warning" style="margin-bottom: 0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
    </div>
    <div class="stat-card" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
        <div class="stat-content"><div class="stat-label">Total Hours</div><div class="stat-value">{{ rtrim(rtrim(number_format($stats['total_hours'], 1, '.', ''), '0'), '.') }}</div></div>
        <div class="stat-icon stat-icon-success" style="margin-bottom: 0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
    </div>
</div>

<div class="card" style="border-radius: 0.75rem; background: #ffffff; border: 1px solid var(--border); margin-top: 1rem;">
    <div class="card-header">
        <h3 class="card-title">Daily Punches</h3>
    </div>
    <div class="card-body" style="padding-top: 0.75rem;">
        <div style="overflow-x: auto; border: 1px solid var(--border); border-radius: 0.75rem;">
            <table class="table" style="min-width: 800px; margin: 0;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="font-weight: 700; color: #0f172a;">Employee</th>
                        <th style="font-weight: 700; color: #0f172a;">Check In</th>
                        <th style="font-weight: 700; color: #0f172a;">Check Out</th>
                        <th style="font-weight: 700; color: #0f172a;">Hours</th>
                        <th style="font-weight: 700; color: #0f172a;">Status</th>
                        <th style="font-weight: 700; color: #0f172a;">Remarks</th>
                        @if ($isAdmin ?? false)
                            <th style="font-weight: 700; color: #0f172a; text-align: center;">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $attendance)
                        @php
                            $isLate = (bool) $attendance->late_status;
                            $isEarlyLeave = (bool) $attendance->early_leave_status;
                            $checkIn = $formatAttendanceTime($attendance->check_in_utc);
                            $checkOut = $formatAttendanceTime($attendance->check_out_utc);
                            $statusValue = $isLate && $isEarlyLeave
                                ? 'late_and_early_leave'
                                : ($isLate ? 'late' : ($isEarlyLeave ? 'early_leave' : 'on_time'));
                            $employeeLabel = $attendance->employee?->user?->name ?? $attendance->employee?->name ?? $attendance->employee_name ?? 'Unknown';
                        @endphp
                        <tr style="background: {{ $isLate || $isEarlyLeave ? '#fef2f2' : '#ffffff' }};">
                            <td style="font-weight: 600; color: #111827;">
                                {{ $employeeLabel }}
                            </td>
                            <td style="font-variant-numeric: tabular-nums;">
                                @if ($checkIn)
                                    <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; width: fit-content; padding: 0.15rem 0.55rem; border-radius: 8px; background: {{ $isLate ? '#fee2e2' : '#dcfce7' }};">
                                            <span style="font-weight: 700; color: {{ $isLate ? '#b91c1c' : '#166534' }};">{{ $checkIn['time'] }}</span>
                                            <span style="font-size: 0.7rem; font-weight: 600; color: {{ $isLate ? '#b91c1c' : '#166534' }};">{{ $checkIn['period'] }}</span>
                                        </span>
                                        @if ($isLate)
                                            <span style="display: inline-flex; align-items: center; width: fit-content; padding: 0.1rem 0.5rem; border-radius: 999px; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.02em; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;">Late</span>
                                        @endif
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="font-variant-numeric: tabular-nums;">
                                @if ($checkOut)
                                    <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; width: fit-content; padding: 0.15rem 0.55rem; border-radius: 8px; background: {{ $isEarlyLeave ? '#fee2e2' : '#dcfce7' }};">
                                            <span style="font-weight: 700; color: {{ $isEarlyLeave ? '#b91c1c' : '#166534' }};">{{ $checkOut['time'] }}</span>
                                            <span style="font-size: 0.7rem; font-weight: 600; color: {{ $isEarlyLeave ? '#b91c1c' : '#166534' }};">{{ $checkOut['period'] }}</span>
                                        </span>
                                        @if ($isEarlyLeave)
                                            <span style="display: inline-flex; align-items: center; width: fit-content; padding: 0.1rem 0.5rem; border-radius: 999px; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.02em; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;">Early Leave</span>
                                        @endif
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $attendance->total_work_minutes !== null ? rtrim(rtrim(number_format($attendance->total_work_minutes / 60, 2, '.', ''), '0'), '.').'h' : '—' }}</td>
                            <td>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                    @if ($isLate)
                                        <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #b91c1c;">Late</span>
                                    @endif
                                    @if ($isEarlyLeave)
                                        <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #b91c1c;">Early Leave</span>
                                    @endif
                                    @if (! $isLate && ! $isEarlyLeave)
                                        <span style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: #dcfce7; color: #166534;">On Time</span>
                                    @endif
                                </div>
                            </td>
                            <td style="color: #6b7280;">{{ $attendance->remarks ?? '—' }}</td>
                            @if ($isAdmin ?? false)
                                <td style="text-align: center;">
                                    <button
                                        type="button"
                                        data-attendance-edit-trigger
                                        data-id="{{ $attendance->id }}"
                                        data-name="{{ $employeeLabel }}"
                                        data-check-in="{{ $attendance->check_in_utc?->format('H:i') }}"
                                        data-check-out="{{ $attendance->check_out_utc?->format('H:i') }}"
                                        data-status="{{ $statusValue }}"
                                        data-remarks="{{ $attendance->remarks }}"
                                        title="Edit attendance"
                                        style="display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: 8px; border: 1px solid var(--border); background: #ffffff; color: #475569; cursor: pointer; transition: all 0.15s ease;"
                                        onmouseover="this.style.background='#f1f5f9'"
                                        onmouseout="this.style.background='#ffffff'"
                                    >
                                        <svg viewBox="0 0 24 24" style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                        </svg>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($isAdmin ?? false) ? 7 : 6 }}" style="text-align: center; color: #6b7280; padding: 1.5rem;">No attendance records for this date yet. Click "Sync Data" to pull from the device data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
