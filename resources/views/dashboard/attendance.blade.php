@extends('tyro-dashboard::layouts.admin')

@section('title', 'Attendance')

@section('breadcrumb')
    <a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Attendance</span>
@endsection

@php
    $currentDate = \Carbon\Carbon::parse($date);
    $prevDate = $currentDate->copy()->subDay()->toDateString();
    $nextDate = $currentDate->copy()->addDay()->toDateString();
    $isToday = $currentDate->isToday();
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-row">
            <div>
                <h1 class="page-title">Attendance</h1>
                <p class="page-description" data-attendance-date-label style="font-size: 1rem;">
                    {{ $currentDate->format('l, F j, Y') }}
                </p>
            </div>

            {{-- Single unified action row --}}
            <div class="attendance-toolbar">
                {{-- Date navigation + filter form --}}
                <form method="GET" action="{{ route('attendances.index') }}" data-attendance-filter-form
                    class="d-flex align-items-center flex-nowrap gap-2">

                    <a href="{{ route('attendances.index', ['date' => $prevDate]) }}" data-attendance-date-link
                        data-attendance-prev-link class="btn btn-icon btn-secondary" title="Previous day"
                        aria-label="Previous day">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </a>

                    <input id="attendance-date" type="date" name="date" value="{{ $date }}"
                        data-attendance-date-input class="attendance-date-input" aria-label="Date">

                    <a href="{{ route('attendances.index', ['date' => $nextDate]) }}" data-attendance-date-link
                        data-attendance-next-link
                        class="btn btn-icon btn-secondary {{ $isToday ? 'pointer-events-none opacity-50' : '' }}"
                        title="Next day" aria-label="Next day">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>

                    <a href="{{ route('attendances.index') }}" data-attendance-date-link data-attendance-today-link
                        class="btn btn-secondary {{ $isToday ? 'd-none' : '' }}">
                        Today
                    </a>

                    <button type="submit" class="btn btn-secondary">
                        <svg viewBox="0 0 24 24" style="width:1rem;height:1rem;" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        View
                    </button>

                    <button type="submit" name="sync" value="1" class="btn btn-sync-data">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <path d="M20.49 15a9 9 0 1 1-2-8.12"></path>
                        </svg>
                        Sync Date
                    </button>
                </form>

                <span class="attendance-toolbar-divider" aria-hidden="true"></span>

                {{-- Bulk actions --}}
                <div class="attendance-toolbar-group">
                    <a href="{{ route('attendances.export', ['start_date' => $date, 'end_date' => $date]) }}"
                        data-attendance-export-link class="btn btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 10l5 5 5-5" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15V3" />
                        </svg>
                        Export CSV
                    </a>

                    @if ($isAdmin && $devices->isNotEmpty())
                        <button type="button" onclick="syncTodayAllDevices()" data-device-sync-trigger
                            class="btn btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            Sync Today
                        </button>
                        <button type="button" onclick="importAllDevicesData()" data-device-sync-trigger
                            class="btn btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline stroke-linecap="round" stroke-linejoin="round" points="7 10 12 15 17 10">
                                </polyline>
                                <line stroke-linecap="round" stroke-linejoin="round" x1="12" y1="15"
                                    x2="12" y2="3"></line>
                            </svg>
                            Import Device Data
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div id="attendance-toast" role="status" aria-live="polite" class="attendance-toast"></div>

    @if ($isAdmin && $devices->isNotEmpty())
        {{-- Device Sync Progress Modal --}}
        <div id="deviceSyncProgressModal" class="device-sync-modal">
            <div class="device-sync-modal-card">
                <div class="device-sync-modal-header">
                    <h3 id="deviceSyncProgressTitle">Device Sync</h3>
                    <button type="button" onclick="closeDeviceSyncProgressModal()" class="device-sync-modal-close"
                        aria-label="Close">×</button>
                </div>
                <div class="device-sync-modal-body">
                    <p id="deviceSyncProgressStatus" class="device-sync-status">Waiting to start...</p>
                    <div class="device-sync-progress-track">
                        <div id="deviceSyncProgressBar" class="device-sync-progress-bar"></div>
                    </div>
                    <p id="deviceSyncProgressPercent" class="device-sync-progress-percent">0%</p>
                    <pre id="deviceSyncProgressLog" class="device-sync-log">Waiting to start...</pre>
                </div>
            </div>
        </div>
    @endif

    <div id="attendance-panel" data-attendance-panel>
        @include('dashboard.partials.attendance-panel', [
            'date' => $date,
            'attendances' => $attendances,
            'syncResult' => $syncResult,
            'stats' => $stats,
            'isAdmin' => $isAdmin,
        ])
    </div>

    @if ($isAdmin)
        <div id="attendance-edit-modal" class="modal-overlay">
            <div class="modal-container" style="max-width: 30rem;">
                <div class="modal-content-wrapper">
                    <div class="modal-body">
                        <div class="modal-body-inner">
                            <div class="modal-icon" style="background: #eef2ff; color: #4338ca;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M11 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                </svg>
                            </div>
                            <div class="modal-text-content" style="width: 100%;">
                                <h2 class="modal-title">Edit Attendance</h2>
                                <p class="modal-message" data-attendance-edit-employee-name>—</p>

                                <form id="attendance-edit-form" class="edit-attendance-form">
                                    <div class="edit-attendance-time-row">
                                        <div class="form-group">
                                            <label for="attendance-edit-check-in" class="form-label">Check In</label>
                                            <input type="time" id="attendance-edit-check-in" class="form-input">
                                        </div>
                                        <div class="form-group">
                                            <label for="attendance-edit-check-out" class="form-label">Check Out</label>
                                            <input type="time" id="attendance-edit-check-out" class="form-input">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="attendance-edit-status" class="form-label">Status</label>
                                        <select id="attendance-edit-status" class="form-select">
                                            <option value="on_time">On Time</option>
                                            <option value="late">Late</option>
                                            <option value="early_leave">Early Leave</option>
                                            <option value="late_and_early_leave">Late & Early Leave</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="attendance-edit-remarks" class="form-label">Remarks</label>
                                        <textarea id="attendance-edit-remarks" class="form-input" rows="3"></textarea>
                                    </div>

                                    <p data-attendance-edit-error class="edit-attendance-error"></p>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal-cancel" data-attendance-edit-cancel>Cancel</button>
                        <button type="button" class="btn-modal-confirm" data-attendance-edit-submit>Save Changes</button>
                    </div>
                </div>
                <button type="button" class="modal-close" data-attendance-edit-cancel>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endif
@endsection

@push('styles')
    <style>
        /* ---- Toolbar layout: single row, grouped, wraps gracefully ---- */
        .attendance-toolbar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .attendance-toolbar-group {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .attendance-toolbar-divider {
            width: 1px;
            height: 1.75rem;
            background: #e5e7eb;
            flex-shrink: 0;
        }

        .attendance-date-input {
            width: 180px;
            height: 42px;
            padding: 0 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #fff;
            color: #374151;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            outline: none;
            cursor: pointer;
        }

        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.4rem;
            height: 2.4rem;
            padding: 0;
        }

        .btn-icon svg {
            width: 1rem;
            height: 1rem;
        }

        .btn-secondary,
        .btn-sync-data {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            white-space: nowrap;
        }

        .btn-secondary svg,
        .btn-sync-data svg {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
        }

        .d-none {
            display: none !important;
        }

        /* ---- Sync Date (primary action) ---- */
        .btn-sync-data {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(67, 56, 202, 0.35);
        }

        .btn-sync-data:hover {
            background: linear-gradient(135deg, #4338ca, #3730a3);
            box-shadow: 0 2px 6px rgba(67, 56, 202, 0.45);
        }

        .btn-sync-data:active {
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.25);
        }

        /* ---- Toast ---- */
        .attendance-toast {
            display: none;
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 1000;
            max-width: 22rem;
            padding: 0.75rem 1rem;
            border-radius: 0.65rem;
            background: #0f172a;
            color: #f8fafc;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.25);
            font-size: 0.875rem;
        }

        /* ---- Edit modal helpers ---- */
        .edit-attendance-form {
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
        }

        .edit-attendance-form .form-group {
            margin: 0;
        }

        .edit-attendance-time-row {
            display: flex;
            gap: 0.75rem;
        }

        .edit-attendance-time-row .form-group {
            flex: 1;
        }

        .edit-attendance-error {
            display: none;
            color: #b91c1c;
            font-size: 0.8rem;
            margin: 0;
        }

        /* ---- Device sync modal ---- */
        .device-sync-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9500;
            background: rgba(0, 0, 0, 0.45);
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .device-sync-modal-card {
            background: #fff;
            border-radius: 0.875rem;
            width: 100%;
            max-width: 34rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            color: #000;
        }

        .device-sync-modal-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .device-sync-modal-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
        }

        .device-sync-modal-close {
            border: none;
            background: none;
            font-size: 1.5rem;
            line-height: 1;
            color: #6b7280;
            cursor: pointer;
        }

        .device-sync-modal-body {
            padding: 1rem 1.25rem;
        }

        .device-sync-status {
            margin: 0 0 0.75rem;
            font-size: 0.875rem;
            color: #374151;
        }

        .device-sync-progress-track {
            height: 0.625rem;
            background: #e5e7eb;
            border-radius: 999px;
            overflow: hidden;
        }

        .device-sync-progress-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #10b981, #059669);
            transition: width 0.2s ease;
        }

        .device-sync-progress-percent {
            margin: 0.5rem 0 0;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .device-sync-log {
            margin-top: 0.9rem;
            max-height: 240px;
            overflow-y: auto;
            background: #0f172a;
            color: #e2e8f0;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            line-height: 1.4;
            white-space: pre-wrap;
        }

        .attendance-toolbar-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .attendance-date-input:hover {
            border-color: #9ca3af;
        }

        .attendance-date-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        /* Calendar icon */
        .attendance-date-input::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .attendance-date-input::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .attendance-toolbar-divider {
                display: none;
            }

            .attendance-date-input {
                min-width: 100%;
            }

            .attendance-toolbar-group {
                flex-wrap: nowrap;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            var panel = document.getElementById('attendance-panel');
            var toast = document.getElementById('attendance-toast');
            var toastTimer = null;
            var baseUrl = @json(route('attendances.index'));

            function showToast(message) {
                if (!message) return;

                toast.textContent = message;
                toast.style.display = 'block';

                clearTimeout(toastTimer);
                toastTimer = setTimeout(function() {
                    toast.style.display = 'none';
                }, 5000);
            }

            function setLoading(isLoading) {
                panel.style.opacity = isLoading ? '0.6' : '1';
                panel.style.pointerEvents = isLoading ? 'none' : 'auto';
            }

            function updateExportLink(date) {
                var exportLink = document.querySelector('[data-attendance-export-link]');
                if (!exportLink) return;

                var url = new URL(exportLink.href, window.location.origin);
                url.searchParams.set('start_date', date);
                url.searchParams.set('end_date', date);
                exportLink.href = url.toString();
            }

            function setLinkDate(link, date) {
                if (!link) return;

                var url = new URL(link.href, window.location.origin);
                url.searchParams.set('date', date);
                link.href = url.toString();
            }

            function updateDateNavControls(data) {
                var dateInput = document.getElementById('attendance-date');
                if (dateInput) dateInput.value = data.date;

                setLinkDate(document.querySelector('[data-attendance-prev-link]'), data.prevDate);

                var nextLink = document.querySelector('[data-attendance-next-link]');
                if (nextLink) {
                    setLinkDate(nextLink, data.nextDate);
                    nextLink.classList.toggle('pointer-events-none', !!data.isToday);
                    nextLink.classList.toggle('opacity-50', !!data.isToday);
                }

                var todayLink = document.querySelector('[data-attendance-today-link]');
                if (todayLink) {
                    todayLink.classList.toggle('d-none', !!data.isToday);
                }
            }

            function loadAttendance(params, options) {
                options = options || {};

                var url = new URL(baseUrl, window.location.origin);
                Object.keys(params).forEach(function(key) {
                    if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                        url.searchParams.set(key, params[key]);
                    }
                });

                setLoading(true);

                fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                    })
                    .then(function(response) {
                        if (!response.ok) throw new Error('Request failed with status ' + response.status);
                        return response.json();
                    })
                    .then(function(data) {
                        panel.innerHTML = data.html;
                        updateExportLink(data.date);
                        updateDateNavControls(data);

                        var dateLabel = document.querySelector('[data-attendance-date-label]');
                        if (dateLabel && data.dateLabel) dateLabel.textContent = data.dateLabel;

                        var pushUrl = new URL(baseUrl, window.location.origin);
                        pushUrl.searchParams.set('date', data.date);
                        window.history.pushState({
                            date: data.date
                        }, '', pushUrl.toString());

                        if (!options.silent && data.syncResult && data.syncResult.message) {
                            showToast(data.syncResult.message);
                        }
                    })
                    .catch(function() {
                        showToast('Could not load attendance data. Please try again.');
                    })
                    .finally(function() {
                        setLoading(false);
                    });
            }

            document.addEventListener('submit', function(event) {
                var form = event.target.closest('[data-attendance-filter-form]');
                if (!form) return;

                event.preventDefault();

                var formData = new FormData(form);
                var submitter = event.submitter;
                var params = {
                    date: formData.get('date')
                };

                if (submitter && submitter.name === 'sync') {
                    params.sync = 1;
                }

                loadAttendance(params);
            });

            document.addEventListener('click', function(event) {
                var link = event.target.closest('[data-attendance-date-link]');
                if (!link || link.classList.contains('pointer-events-none')) return;

                event.preventDefault();

                var url = new URL(link.href, window.location.origin);
                var date = url.searchParams.get('date') || @json(now()->toDateString());

                loadAttendance({
                    date: date
                });
            });

            window.addEventListener('popstate', function() {
                var url = new URL(window.location.href);
                var date = url.searchParams.get('date') || @json(now()->toDateString());

                loadAttendance({
                    date: date
                }, {
                    silent: true
                });
            });

            var editModal = document.getElementById('attendance-edit-modal');

            if (editModal) {
                var editForm = document.getElementById('attendance-edit-form');
                var editError = editModal.querySelector('[data-attendance-edit-error]');
                var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                function openEditModal(trigger) {
                    editForm.dataset.attendanceId = trigger.dataset.id;
                    editModal.querySelector('[data-attendance-edit-employee-name]').textContent = trigger.dataset
                        .name || '';
                    document.getElementById('attendance-edit-check-in').value = trigger.dataset.checkIn || '';
                    document.getElementById('attendance-edit-check-out').value = trigger.dataset.checkOut || '';
                    document.getElementById('attendance-edit-status').value = trigger.dataset.status || 'on_time';
                    document.getElementById('attendance-edit-remarks').value = trigger.dataset.remarks || '';
                    editError.style.display = 'none';
                    editModal.classList.add('active');
                }

                function closeEditModal() {
                    editModal.classList.remove('active');
                }

                function submitEditForm() {
                    var id = editForm.dataset.attendanceId;
                    editError.style.display = 'none';

                    fetch(baseUrl.replace(/\/$/, '') + '/' + id + '/quick-update', {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                            },
                            body: JSON.stringify({
                                check_in: document.getElementById('attendance-edit-check-in').value || null,
                                check_out: document.getElementById('attendance-edit-check-out').value ||
                                    null,
                                status: document.getElementById('attendance-edit-status').value,
                                remarks: document.getElementById('attendance-edit-remarks').value,
                            }),
                        })
                        .then(function(response) {
                            return response.json().then(function(data) {
                                if (!response.ok) throw new Error(data.message ||
                                    'Could not update attendance.');
                                return data;
                            });
                        })
                        .then(function(data) {
                            closeEditModal();
                            showToast(data.message || 'Attendance record updated.');

                            var currentDate = document.getElementById('attendance-date');
                            loadAttendance({
                                date: currentDate ? currentDate.value : null
                            }, {
                                silent: true
                            });
                        })
                        .catch(function(err) {
                            editError.textContent = err.message;
                            editError.style.display = 'block';
                        });
                }

                document.addEventListener('click', function(event) {
                    var trigger = event.target.closest('[data-attendance-edit-trigger]');
                    if (trigger) {
                        openEditModal(trigger);
                        return;
                    }

                    if (event.target.closest('[data-attendance-edit-cancel]')) {
                        closeEditModal();
                    }

                    if (event.target.closest('[data-attendance-edit-submit]')) {
                        submitEditForm();
                    }
                });
            }

            window.__attendanceLoad = loadAttendance;
            window.__attendanceShowToast = showToast;
            window.__attendanceCurrentDate = function() {
                var currentDate = document.getElementById('attendance-date');
                return currentDate ? currentDate.value : null;
            };
        })();
    </script>
@endpush

@if ($isAdmin && $devices->isNotEmpty())
    @push('scripts')
        <script>
            var attendanceSyncDevices = @json($devices->map(fn($device) => ['id' => $device->id, 'name' => $device->name]));

            function openDeviceSyncProgressModal(title) {
                document.getElementById('deviceSyncProgressModal').style.display = 'flex';
                document.getElementById('deviceSyncProgressTitle').textContent = title;
                document.getElementById('deviceSyncProgressLog').textContent = 'Waiting to start...';
                document.getElementById('deviceSyncProgressStatus').textContent = 'Waiting to start...';
                document.getElementById('deviceSyncProgressBar').style.width = '0%';
                document.getElementById('deviceSyncProgressPercent').textContent = '0%';
            }

            function closeDeviceSyncProgressModal() {
                document.getElementById('deviceSyncProgressModal').style.display = 'none';
            }

            function appendDeviceSyncLog(message) {
                var log = document.getElementById('deviceSyncProgressLog');
                log.textContent += '\n' + message;
                log.scrollTop = log.scrollHeight;
            }

            function updateDeviceSyncProgress(payload) {
                var percentValue = Number(payload.percentage || 0);
                document.getElementById('deviceSyncProgressStatus').textContent = payload.message || 'Processing...';
                document.getElementById('deviceSyncProgressBar').style.width = percentValue + '%';
                document.getElementById('deviceSyncProgressPercent').textContent = percentValue + '%';
            }

            function setDeviceSyncTriggersDisabled(disabled) {
                document.querySelectorAll('[data-device-sync-trigger]').forEach(function(button) {
                    button.disabled = disabled;
                    button.style.opacity = disabled ? '0.6' : '1';
                    button.style.cursor = disabled ? 'not-allowed' : 'pointer';
                });
            }

            async function runDeviceSyncStream(endpointSuffix, device, totals) {
                appendDeviceSyncLog('');
                appendDeviceSyncLog('=== ' + device.name + ' ===');

                let response;

                try {
                    response = await fetch('/dashboard/devices/' + device.id + endpointSuffix, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            Accept: 'text/plain',
                        },
                    });
                } catch (error) {
                    appendDeviceSyncLog('Failed to reach ' + device.name + ': ' + (error.message || 'Unknown error'));
                    return;
                }

                if (!response.ok || !response.body) {
                    appendDeviceSyncLog(device.name + ' failed: unable to connect to the device endpoint.');
                    return;
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const {
                        done,
                        value
                    } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, {
                        stream: true
                    });
                    const lines = buffer.split('\n');
                    buffer = lines.pop() || '';

                    for (const line of lines) {
                        const trimmed = line.trim();
                        if (!trimmed) continue;

                        let payload;
                        try {
                            payload = JSON.parse(trimmed);
                        } catch (error) {
                            continue;
                        }

                        if (payload.type === 'status') {
                            appendDeviceSyncLog(payload.message || 'Starting...');
                        }
                        if (payload.type === 'progress') {
                            updateDeviceSyncProgress(payload);
                        }
                        if (payload.type === 'error') {
                            appendDeviceSyncLog('Error: ' + (payload.message || 'Unknown error'));
                        }
                        if (payload.type === 'complete') {
                            totals.inserted += Number(payload.inserted || 0);
                            appendDeviceSyncLog(device.name + ': inserted ' + (payload.inserted || 0) + ', skipped ' + (
                                payload.skipped || 0) + ', failed ' + (payload.failed || 0) + '.');
                        }
                    }
                }
            }

            async function runDeviceSyncForAllDevices(endpointSuffix, title) {
                setDeviceSyncTriggersDisabled(true);
                openDeviceSyncProgressModal(title);

                const totals = {
                    inserted: 0
                };

                for (let i = 0; i < attendanceSyncDevices.length; i++) {
                    const device = attendanceSyncDevices[i];
                    document.getElementById('deviceSyncProgressStatus').textContent =
                        'Device ' + (i + 1) + ' of ' + attendanceSyncDevices.length + ': ' + device.name;

                    await runDeviceSyncStream(endpointSuffix, device, totals);
                }

                updateDeviceSyncProgress({
                    percentage: 100,
                    message: 'All devices completed.'
                });
                appendDeviceSyncLog('');
                appendDeviceSyncLog('Done. ' + totals.inserted + ' new punch record(s) inserted in total.');

                setDeviceSyncTriggersDisabled(false);

                if (window.__attendanceLoad) {
                    window.__attendanceLoad({
                        date: window.__attendanceCurrentDate()
                    }, {
                        silent: true
                    });
                }
                if (window.__attendanceShowToast) {
                    window.__attendanceShowToast(title + ' completed: ' + totals.inserted + ' new punch record(s).');
                }
            }

            function syncTodayAllDevices() {
                showConfirm('Sync Today', 'Sync today\'s attendance records from all ' + attendanceSyncDevices.length +
                    ' device(s)?').then(function(confirmed) {
                    if (!confirmed) return;
                    runDeviceSyncForAllDevices('/sync-today', 'Sync Today');
                });
            }

            function importAllDevicesData() {
                showConfirm('Import Device Data', 'Import attendance records from all ' + attendanceSyncDevices.length +
                    ' device(s)?').then(function(confirmed) {
                    if (!confirmed) return;
                    runDeviceSyncForAllDevices('/import-attendance', 'Import Device Data');
                });
            }

            document.getElementById('deviceSyncProgressModal').addEventListener('click', function(e) {
                if (e.target === this) closeDeviceSyncProgressModal();
            });
        </script>
    @endpush
@endif
