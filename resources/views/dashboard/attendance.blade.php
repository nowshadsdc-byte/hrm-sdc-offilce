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
                <form method="GET" action="{{ route('dashboard.attendance') }}">
                    <div style="display: flex; gap: 0.5rem;">
                        <input id="attendance-date" type="date" name="date"
                            value="{{ request('date', $selectedDate ?? now()->toDateString()) }}" class="form-input"
                            onchange="this.form.submit()">
                        <a href="{{ route('attendances.export', ['start_date' => request('date', $selectedDate ?? now()->toDateString()), 'end_date' => request('date', $selectedDate ?? now()->toDateString())]) }}"
                            class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 0.4rem;">
                            <svg viewBox="0 0 24 24" style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 10l5 5 5-5" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15V3" />
                            </svg>
                            Export CSV
                        </a>
                    </div>
                </form>

                <form method="POST" action="{{ route('dashboard.attendance.sync-now') }}" id="sync-form" style="display: contents;">
                    @csrf
                    <input type="hidden" name="date" value="{{ request('date', $selectedDate ?? now()->toDateString()) }}">
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" class="btn btn-primary" id="sync-btn" onclick="handleSyncNow(event)">
                            <span id="sync-btn-text">Sync Now</span>
                            <span id="sync-spinner" style="display: none; margin-left: 0.5rem;">
                                <svg style="width: 1rem; height: 1rem; animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" style="opacity: 0.25;"></circle>
                                    <path d="M12 2a10 10 0 0 1 10 10" style="opacity: 1;"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                    <div id="sync-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
                        <div style="background: white; border-radius: 0.75rem; padding: 2rem; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                            <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 700;">Sync Attendance</h3>
                            <div id="sync-progress" style="background: #f3f4f6; border-radius: 0.5rem; padding: 1rem; max-height: 300px; overflow-y: auto; margin-bottom: 1rem; font-size: 0.875rem; font-family: monospace;">
                                <div style="color: #6b7280;">Starting sync...</div>
                            </div>
                            <div id="sync-status" style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem;"></div>
                            <button type="button" id="sync-close-btn" style="display: none;" onclick="closeSyncModal()" class="btn btn-secondary">Close</button>
                        </div>
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
                                @if ($canAdjustAttendance ?? false)
                                    <th style="font-weight: 700; color: #0f172a;">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $attendance)
                                @php
                                    $checkIn = $attendance->check_in;
                                    $lunchStart = $attendance->lunch_start;
                                    $lunchEnd = $attendance->lunch_end;
                                    $checkOut = $attendance->check_out;
                                    $totalWorkMinutes = $attendance->total_work_minutes;
                                    $overtimeMinutes = $attendance->overtime_minutes;
                                    $lunchDurationMinutes = $attendance->lunch_duration_minutes;
                                    $formatMinutes = static function (?int $minutes): string {
                                        if ($minutes === null) {
                                            return '—';
                                        }

                                        return rtrim(rtrim(number_format($minutes / 60, 2, '.', ''), '0'), '.').'h';
                                    };
                                    $lateStatus = $attendance->late_status ? 'Late' : 'On Time';
                                    $employeeName = $attendance->employee?->user?->name ?? ($attendance->employee?->name ?? ($attendance->employee_name ?? 'N/A'));
                                    $toTimeValue = static function ($datetime): string {
                                        return $datetime ? $datetime->format('H:i') : '';
                                    };
                                @endphp
                                <tr style="background: {{ $loop->odd ? '#ffffff' : '#fcfcfd' }};">
                                    <td style="font-weight: 600; color: #111827;">
                                        {{ $employeeName }}
                                    </td>
                                    <td>{{ $checkIn ? $checkIn->format('h:i A') : '—' }}</td>
                                    <td>{{ $lunchStart ? $lunchStart->format('h:i A') : '—' }}</td>
                                    <td>{{ $lunchEnd ? $lunchEnd->format('h:i A') : '—' }}</td>
                                    <td>{{ $formatMinutes($lunchDurationMinutes) }}</td>
                                    <td>{{ $checkOut ? $checkOut->format('h:i A') : '—' }}</td>
                                    <td>{{ $formatMinutes($totalWorkMinutes) }}</td>
                                    <td>{{ $formatMinutes($overtimeMinutes) }}</td>
                                    <td>
                                        <span
                                            style="display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: {{ $lateStatus === 'Late' ? '#fef3c7' : '#dcfce7' }}; color: {{ $lateStatus === 'Late' ? '#92400e' : '#166534' }};">
                                            {{ $lateStatus }}
                                        </span>
                                    </td>
                                    <td style="color: #6b7280;">
                                        {{ $attendance->remarks ?? ($checkIn ? 'Recorded' : 'No record') }}
                                    </td>
                                    @if ($canAdjustAttendance ?? false)
                                        <td>
                                            <button type="button"
                                                onclick="openAdjustModal({{ json_encode([
                                                    'id' => $attendance->id,
                                                    'employee_name' => $employeeName,
                                                    'date' => $attendance->date?->toDateString(),
                                                    'check_in_time' => $toTimeValue($checkIn),
                                                    'lunch_start_time' => $toTimeValue($lunchStart),
                                                    'lunch_end_time' => $toTimeValue($lunchEnd),
                                                    'check_out_time' => $toTimeValue($checkOut),
                                                    'lunch_duration_minutes' => $lunchDurationMinutes,
                                                    'total_work_minutes' => $totalWorkMinutes,
                                                    'overtime_minutes' => $overtimeMinutes,
                                                    'remarks' => $attendance->remarks ?? '',
                                                    'update_url' => route('dashboard.attendance.adjust', $attendance),
                                                ]) }})"
                                                title="Edit attendance"
                                                style="display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border: 1px solid var(--border); border-radius: 0.5rem; background: #fff; color: #2563eb; cursor: pointer;">
                                                <svg viewBox="0 0 24 24" style="width: 1rem; height: 1rem;" fill="none"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                </svg>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ ($canAdjustAttendance ?? false) ? 11 : 10 }}"
                                        style="text-align: center; color: #6b7280; padding: 1.5rem;">No
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

    @if ($canAdjustAttendance ?? false)
        <div id="adjust-modal"
            style="display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,0.45); align-items: center; justify-content: center; padding: 1rem;">
            <div
                style="background: white; border-radius: 1rem; width: 100%; max-width: 36rem; box-shadow: 0 20px 40px rgba(0,0,0,0.15); max-height: 90vh; overflow-y: auto;">
                <div
                    style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Adjust Attendance</h3>
                        <p id="adjust-modal-subtitle" class="muted-text" style="font-size: 0.85rem; margin: 0.25rem 0 0;">
                        </p>
                    </div>
                    <button type="button" onclick="closeAdjustModal()"
                        style="background: none; border: none; cursor: pointer; color: var(--muted);">
                        <svg viewBox="0 0 24 24" style="width: 1.25rem; height: 1.25rem;" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form id="adjust-form" method="POST" style="padding: 1.25rem 1.5rem 1.5rem;" onsubmit="prepareAdjustFormSubmit()">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="date" id="adjust-date-hidden"
                        value="{{ request('date', $selectedDate ?? now()->toDateString()) }}">

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label for="adjust-check-in" class="form-label">Check In</label>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center;">
                                <select id="adjust-check-in-mode" class="form-input adjust-time-mode"
                                    style="width: auto; min-width: 7.5rem; font-size: 0.8rem; padding: 0.35rem 0.5rem;"
                                    onchange="syncTimeFieldMode('check-in')">
                                    <option value="blank">— Blank —</option>
                                    <option value="time">Set time</option>
                                </select>
                                <input type="time" id="adjust-check-in" name="check_in_time" class="form-input adjust-time-input"
                                    style="flex: 1; min-width: 8rem;">
                                <button type="button" onclick="clearTimeField('check-in')" class="btn btn-secondary"
                                    style="font-size: 0.75rem; padding: 0.3rem 0.55rem;">Remove</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="adjust-check-out" class="form-label">Check Out</label>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center;">
                                <select id="adjust-check-out-mode" class="form-input adjust-time-mode"
                                    style="width: auto; min-width: 7.5rem; font-size: 0.8rem; padding: 0.35rem 0.5rem;"
                                    onchange="syncTimeFieldMode('check-out')">
                                    <option value="blank">— Blank —</option>
                                    <option value="time">Set time</option>
                                </select>
                                <input type="time" id="adjust-check-out" name="check_out_time" class="form-input adjust-time-input"
                                    style="flex: 1; min-width: 8rem;">
                                <button type="button" onclick="clearTimeField('check-out')" class="btn btn-secondary"
                                    style="font-size: 0.75rem; padding: 0.3rem 0.55rem;">Remove</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="adjust-lunch-start" class="form-label">Lunch Start</label>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center;">
                                <select id="adjust-lunch-start-mode" class="form-input adjust-time-mode"
                                    style="width: auto; min-width: 7.5rem; font-size: 0.8rem; padding: 0.35rem 0.5rem;"
                                    onchange="syncTimeFieldMode('lunch-start')">
                                    <option value="blank">— Blank —</option>
                                    <option value="time">Set time</option>
                                </select>
                                <input type="time" id="adjust-lunch-start" name="lunch_start_time"
                                    class="form-input adjust-time-input" style="flex: 1; min-width: 8rem;">
                                <button type="button" onclick="clearTimeField('lunch-start')" class="btn btn-secondary"
                                    style="font-size: 0.75rem; padding: 0.3rem 0.55rem;">Remove</button>
                            </div>
                            <div style="margin-top: 0.45rem; display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center;">
                                <select id="adjust-lunch-start-action" name="lunch_start_action" class="form-input"
                                    style="width: auto; min-width: 11rem; font-size: 0.8rem; padding: 0.35rem 0.5rem;"
                                    onchange="applyPunchAction('lunch-start', this.value, false)">
                                    <option value="keep">Keep as lunch start</option>
                                    <option value="remove">Remove lunch start</option>
                                    <option value="use_as_check_out">Use as check out</option>
                                </select>
                                <button type="button" onclick="applyPunchAction('lunch-start', 'remove')"
                                    class="btn btn-secondary"
                                    style="font-size: 0.75rem; padding: 0.3rem 0.55rem;">Clear punch</button>
                                <button type="button" onclick="applyPunchAction('lunch-start', 'use_as_check_out')"
                                    class="btn btn-secondary"
                                    style="font-size: 0.75rem; padding: 0.3rem 0.55rem;">Move to check out</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="adjust-lunch-end" class="form-label">Lunch End</label>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center;">
                                <select id="adjust-lunch-end-mode" class="form-input adjust-time-mode"
                                    style="width: auto; min-width: 7.5rem; font-size: 0.8rem; padding: 0.35rem 0.5rem;"
                                    onchange="syncTimeFieldMode('lunch-end')">
                                    <option value="blank">— Blank —</option>
                                    <option value="time">Set time</option>
                                </select>
                                <input type="time" id="adjust-lunch-end" name="lunch_end_time"
                                    class="form-input adjust-time-input" style="flex: 1; min-width: 8rem;">
                                <button type="button" onclick="clearTimeField('lunch-end')" class="btn btn-secondary"
                                    style="font-size: 0.75rem; padding: 0.3rem 0.55rem;">Remove</button>
                            </div>
                            <div style="margin-top: 0.45rem; display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center;">
                                <select id="adjust-lunch-end-action" name="lunch_end_action" class="form-input"
                                    style="width: auto; min-width: 11rem; font-size: 0.8rem; padding: 0.35rem 0.5rem;"
                                    onchange="applyPunchAction('lunch-end', this.value, false)">
                                    <option value="keep">Keep as lunch end</option>
                                    <option value="remove">Remove lunch end</option>
                                    <option value="use_as_check_out">Use as check out</option>
                                </select>
                                <button type="button" onclick="applyPunchAction('lunch-end', 'remove')"
                                    class="btn btn-secondary"
                                    style="font-size: 0.75rem; padding: 0.3rem 0.55rem;">Clear punch</button>
                                <button type="button" onclick="applyPunchAction('lunch-end', 'use_as_check_out')"
                                    class="btn btn-secondary"
                                    style="font-size: 0.75rem; padding: 0.3rem 0.55rem;">Move to check out</button>
                            </div>
                            <p class="muted-text" style="font-size: 0.75rem; margin: 0.35rem 0 0;">
                                Choose blank or remove when a punch should be cleared. Use move to check out when a lunch punch is actually checkout.
                            </p>
                        </div>
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; margin-top: 0.5rem;">
                        <div class="form-group">
                            <label for="adjust-lunch-duration" class="form-label">Lunch (minutes)</label>
                            <input type="number" id="adjust-lunch-duration" name="lunch_duration_minutes"
                                class="form-input" min="0" step="1" placeholder="Auto">
                        </div>
                        <div class="form-group">
                            <label for="adjust-total-work" class="form-label">Hours (minutes)</label>
                            <input type="number" id="adjust-total-work" name="total_work_minutes" class="form-input"
                                min="0" step="1" placeholder="Auto">
                        </div>
                        <div class="form-group">
                            <label for="adjust-overtime" class="form-label">Overtime (minutes)</label>
                            <input type="number" id="adjust-overtime" name="overtime_minutes" class="form-input"
                                min="0" step="1" placeholder="Auto">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 0.75rem;">
                        <label for="adjust-remarks" class="form-label">Remarks</label>
                        <textarea id="adjust-remarks" name="remarks" class="form-input" rows="3"
                            placeholder="Add a note about this adjustment"></textarea>
                    </div>

                    <p class="muted-text" style="font-size: 0.8rem; margin: 0.75rem 0 0;">
                        Leave duration fields empty to auto-calculate from the times above.
                    </p>

                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                        <button type="button" onclick="closeAdjustModal()" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>

    <script>
        function handleSyncNow(event) {
            event.preventDefault();
            const form = document.getElementById('sync-form');
            const syncBtn = document.getElementById('sync-btn');
            const syncBtnText = document.getElementById('sync-btn-text');
            const syncSpinner = document.getElementById('sync-spinner');
            const syncModal = document.getElementById('sync-modal');
            const syncProgress = document.getElementById('sync-progress');
            const syncStatus = document.getElementById('sync-status');
            const closeBtn = document.getElementById('sync-close-btn');

            // Disable button and show loading state
            syncBtn.disabled = true;
            syncBtnText.textContent = 'Syncing...';
            syncSpinner.style.display = 'inline-block';

            // Show modal
            syncModal.style.display = 'flex';
            syncProgress.innerHTML = '<div style="color: #6b7280;">Starting sync...</div>';
            syncStatus.innerHTML = '';
            closeBtn.style.display = 'none';

            // Fetch form data
            const formData = new FormData(form);

            // Start streaming
            fetch(form.action, {
                method: 'POST',
                body: formData,
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';

                    function processStream() {
                        reader.read().then(({ done, value }) => {
                            if (done) {
                                // Stream finished
                                setTimeout(() => {
                                    syncBtn.disabled = false;
                                    syncBtnText.textContent = 'Sync Now';
                                    syncSpinner.style.display = 'none';
                                    closeBtn.style.display = 'block';

                                    // Reload page after 2 seconds
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 2000);
                                }, 500);
                                return;
                            }

                            buffer += decoder.decode(value, { stream: true });
                            const lines = buffer.split('\n');
                            buffer = lines.pop() || '';

                            for (const line of lines) {
                                if (!line.trim()) continue;

                                try {
                                    const event = JSON.parse(line);
                                    handleSyncEvent(event, syncProgress, syncStatus);
                                } catch (e) {
                                    console.error('Failed to parse event', e);
                                }
                            }

                            processStream();
                        });
                    }

                    processStream();
                })
                .catch((error) => {
                    console.error('Sync error', error);
                    syncProgress.innerHTML += `<div style="color: #dc2626; margin-top: 0.5rem;">Error: ${error.message}</div>`;
                    syncBtn.disabled = false;
                    syncBtnText.textContent = 'Sync Now';
                    syncSpinner.style.display = 'none';
                    closeBtn.style.display = 'block';
                });
        }

        function handleSyncEvent(event, progressEl, statusEl) {
            const timestamp = new Date().toLocaleTimeString();

            if (event.type === 'status' || event.type === 'warning' || event.type === 'info') {
                const color = event.type === 'error' ? '#dc2626' : event.type === 'warning' ? '#f59e0b' : '#3b82f6';
                const msg = `<div style="color: ${color}; margin-top: 0.5rem;">[${timestamp}] ${event.message}</div>`;
                progressEl.innerHTML += msg;
                progressEl.scrollTop = progressEl.scrollHeight;
            } else if (event.type === 'progress') {
                const percentage = event.percentage || 0;
                const msg = `${event.processed || 0} of ${event.total || 0} (${percentage}%)`;
                statusEl.textContent = msg;
                const progressMsg = `<div style="color: #6b7280; margin-top: 0.5rem;">[${timestamp}] Processing: ${msg}</div>`;
                progressEl.innerHTML += progressMsg;
                progressEl.scrollTop = progressEl.scrollHeight;
            } else if (event.type === 'complete' || event.type === 'success') {
                let message = event.message || 'Sync completed successfully';
                if (event.stats) {
                    message += ` (Created: ${event.stats.created}, Updated: ${event.stats.updated})`;
                }
                const msg = `<div style="color: #059669; margin-top: 0.5rem; font-weight: 600;">[${timestamp}] ✓ ${message}</div>`;
                progressEl.innerHTML += msg;
                progressEl.scrollTop = progressEl.scrollHeight;
            } else if (event.type === 'error') {
                const msg = `<div style="color: #dc2626; margin-top: 0.5rem; font-weight: 600;">[${timestamp}] ✗ Error: ${event.message}</div>`;
                progressEl.innerHTML += msg;
                progressEl.scrollTop = progressEl.scrollHeight;
            }
        }

        function closeSyncModal() {
            document.getElementById('sync-modal').style.display = 'none';
        }

        const adjustTimeFields = ['check-in', 'check-out', 'lunch-start', 'lunch-end'];
        const adjustPunchFields = ['lunch-start', 'lunch-end'];

        function openAdjustModal(data) {
            const modal = document.getElementById('adjust-modal');
            const form = document.getElementById('adjust-form');
            const subtitle = document.getElementById('adjust-modal-subtitle');

            form.action = data.update_url;
            subtitle.textContent = data.employee_name + ' · ' + data.date;

            setTimeField('check-in', data.check_in_time || '');
            setTimeField('check-out', data.check_out_time || '');
            setTimeField('lunch-start', data.lunch_start_time || '');
            setTimeField('lunch-end', data.lunch_end_time || '');

            document.getElementById('adjust-lunch-duration').value = data.lunch_duration_minutes ?? '';
            document.getElementById('adjust-total-work').value = data.total_work_minutes ?? '';
            document.getElementById('adjust-overtime').value = data.overtime_minutes ?? '';
            document.getElementById('adjust-remarks').value = data.remarks || '';
            document.getElementById('adjust-lunch-start-action').value = 'keep';
            document.getElementById('adjust-lunch-end-action').value = 'keep';

            adjustPunchFields.forEach((fieldKey) => syncPunchFieldState(fieldKey));

            modal.style.display = 'flex';
        }

        function setTimeField(fieldKey, timeValue) {
            const modeSelect = document.getElementById(`adjust-${fieldKey}-mode`);
            const timeInput = document.getElementById(`adjust-${fieldKey}`);

            if (timeValue) {
                modeSelect.value = 'time';
                timeInput.value = timeValue;
            } else {
                modeSelect.value = 'blank';
                timeInput.value = '';
            }

            syncTimeFieldMode(fieldKey);
        }

        function syncTimeFieldMode(fieldKey) {
            const modeSelect = document.getElementById(`adjust-${fieldKey}-mode`);
            const timeInput = document.getElementById(`adjust-${fieldKey}`);
            const isBlank = modeSelect.value === 'blank';

            timeInput.disabled = isBlank;
            timeInput.style.opacity = isBlank ? '0.55' : '1';

            if (isBlank) {
                timeInput.value = '';
            }

            if (adjustPunchFields.includes(fieldKey)) {
                const actionSelect = document.getElementById(`adjust-${fieldKey}-action`);

                if (isBlank && actionSelect.value === 'keep') {
                    actionSelect.value = 'remove';
                } else if (!isBlank && actionSelect.value === 'remove') {
                    actionSelect.value = 'keep';
                }

                syncPunchFieldState(fieldKey);
            }
        }

        function clearTimeField(fieldKey) {
            const modeSelect = document.getElementById(`adjust-${fieldKey}-mode`);
            modeSelect.value = 'blank';
            syncTimeFieldMode(fieldKey);

            if (adjustPunchFields.includes(fieldKey)) {
                applyPunchAction(fieldKey, 'remove');
            }
        }

        function applyPunchAction(fieldKey, action, updateSelect = true) {
            const timeInput = document.getElementById(`adjust-${fieldKey}`);
            const checkOutInput = document.getElementById('adjust-check-out');
            const checkOutMode = document.getElementById('adjust-check-out-mode');
            const lunchDurationInput = document.getElementById('adjust-lunch-duration');
            const totalWorkInput = document.getElementById('adjust-total-work');
            const actionSelect = document.getElementById(`adjust-${fieldKey}-action`);
            const modeSelect = document.getElementById(`adjust-${fieldKey}-mode`);

            if (updateSelect) {
                actionSelect.value = action;
            }

            if (action === 'remove') {
                modeSelect.value = 'blank';
                timeInput.value = '';
                lunchDurationInput.value = '';
            } else if (action === 'use_as_check_out') {
                if (timeInput.value) {
                    checkOutMode.value = 'time';
                    checkOutInput.disabled = false;
                    checkOutInput.style.opacity = '1';
                    checkOutInput.value = timeInput.value;
                }

                modeSelect.value = 'blank';
                timeInput.value = '';
                lunchDurationInput.value = '';
                totalWorkInput.value = '';
            } else if (action === 'keep' && timeInput.value) {
                modeSelect.value = 'time';
            }

            syncTimeFieldMode(fieldKey);
            syncPunchFieldState(fieldKey);
        }

        function syncPunchFieldState(fieldKey) {
            const action = document.getElementById(`adjust-${fieldKey}-action`).value;
            const modeSelect = document.getElementById(`adjust-${fieldKey}-mode`);
            const timeInput = document.getElementById(`adjust-${fieldKey}`);
            const isLocked = action === 'remove' || action === 'use_as_check_out';

            if (isLocked) {
                modeSelect.value = 'blank';
                timeInput.value = '';
            }

            modeSelect.disabled = isLocked;
            timeInput.disabled = isLocked || modeSelect.value === 'blank';
            timeInput.style.opacity = timeInput.disabled ? '0.55' : '1';
            modeSelect.style.opacity = isLocked ? '0.55' : '1';
        }

        function prepareAdjustFormSubmit() {
            adjustTimeFields.forEach((fieldKey) => {
                const modeSelect = document.getElementById(`adjust-${fieldKey}-mode`);
                const timeInput = document.getElementById(`adjust-${fieldKey}`);
                const actionSelect = document.getElementById(`adjust-${fieldKey}-action`);

                modeSelect.disabled = false;
                timeInput.disabled = false;

                if (modeSelect.value === 'blank') {
                    timeInput.value = '';
                }

                if (actionSelect && (actionSelect.value === 'remove' || actionSelect.value === 'use_as_check_out')) {
                    timeInput.value = '';
                }
            });
        }

        function closeAdjustModal() {
            const modal = document.getElementById('adjust-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
    </script>

@endsection
