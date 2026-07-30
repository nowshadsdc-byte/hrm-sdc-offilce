@extends('tyro-dashboard::layouts.admin')

@section('title', 'Attendance')

@section('breadcrumb')
    <a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Attendance</span>
@endsection

@section('content')
    <div class="page-header">
        <div class="page-header-row">
            <div>
                <h1 class="page-title">Attendance</h1>
                <p class="page-description" data-attendance-date-label style="font-size: 1rem;">{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="{{ route('attendances.export', ['start_date' => $date, 'end_date' => $date]) }}" data-attendance-export-link class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 0.4rem;">
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

    <div
        id="attendance-toast"
        role="status"
        aria-live="polite"
        style="display: none; position: fixed; top: 1.25rem; right: 1.25rem; z-index: 1000; max-width: 22rem; padding: 0.75rem 1rem; border-radius: 0.65rem; background: #0f172a; color: #f8fafc; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.25); font-size: 0.875rem;"
    ></div>

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
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                </svg>
                            </div>
                            <div class="modal-text-content" style="width: 100%;">
                                <h2 class="modal-title">Edit Attendance</h2>
                                <p class="modal-message" data-attendance-edit-employee-name>—</p>

                                <form id="attendance-edit-form" style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.9rem;">
                                    <div style="display: flex; gap: 0.75rem;">
                                        <div class="form-group" style="flex: 1; margin: 0;">
                                            <label for="attendance-edit-check-in" class="form-label">Check In</label>
                                            <input type="time" id="attendance-edit-check-in" class="form-input">
                                        </div>
                                        <div class="form-group" style="flex: 1; margin: 0;">
                                            <label for="attendance-edit-check-out" class="form-label">Check Out</label>
                                            <input type="time" id="attendance-edit-check-out" class="form-input">
                                        </div>
                                    </div>

                                    <div class="form-group" style="margin: 0;">
                                        <label for="attendance-edit-status" class="form-label">Status</label>
                                        <select id="attendance-edit-status" class="form-select">
                                            <option value="on_time">On Time</option>
                                            <option value="late">Late</option>
                                            <option value="early_leave">Early Leave</option>
                                            <option value="late_and_early_leave">Late & Early Leave</option>
                                        </select>
                                    </div>

                                    <div class="form-group" style="margin: 0;">
                                        <label for="attendance-edit-remarks" class="form-label">Remarks</label>
                                        <textarea id="attendance-edit-remarks" class="form-input" rows="3"></textarea>
                                    </div>

                                    <p data-attendance-edit-error style="display: none; color: #b91c1c; font-size: 0.8rem; margin: 0;"></p>
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

@push('scripts')
    <script>
        (function () {
            var panel = document.getElementById('attendance-panel');
            var toast = document.getElementById('attendance-toast');
            var toastTimer = null;
            var baseUrl = @json(route('attendances.index'));

            function showToast(message) {
                if (!message) {
                    return;
                }

                toast.textContent = message;
                toast.style.display = 'block';

                clearTimeout(toastTimer);
                toastTimer = setTimeout(function () {
                    toast.style.display = 'none';
                }, 5000);
            }

            function setLoading(isLoading) {
                panel.style.opacity = isLoading ? '0.6' : '1';
                panel.style.pointerEvents = isLoading ? 'none' : 'auto';
            }

            function updateExportLink(date) {
                var exportLink = document.querySelector('[data-attendance-export-link]');
                if (!exportLink) {
                    return;
                }

                var url = new URL(exportLink.href, window.location.origin);
                url.searchParams.set('start_date', date);
                url.searchParams.set('end_date', date);
                exportLink.href = url.toString();
            }

            function loadAttendance(params, options) {
                options = options || {};

                var url = new URL(baseUrl, window.location.origin);
                Object.keys(params).forEach(function (key) {
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
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Request failed with status ' + response.status);
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        panel.innerHTML = data.html;
                        updateExportLink(data.date);

                        var dateLabel = document.querySelector('[data-attendance-date-label]');
                        if (dateLabel && data.dateLabel) {
                            dateLabel.textContent = data.dateLabel;
                        }

                        var pushUrl = new URL(baseUrl, window.location.origin);
                        pushUrl.searchParams.set('date', data.date);
                        window.history.pushState({date: data.date}, '', pushUrl.toString());

                        if (!options.silent && data.syncResult && data.syncResult.message) {
                            showToast(data.syncResult.message);
                        }
                    })
                    .catch(function () {
                        showToast('Could not load attendance data. Please try again.');
                    })
                    .finally(function () {
                        setLoading(false);
                    });
            }

            document.addEventListener('submit', function (event) {
                var form = event.target.closest('[data-attendance-filter-form]');
                if (!form) {
                    return;
                }

                event.preventDefault();

                var formData = new FormData(form);
                var submitter = event.submitter;
                var params = {
                    date: formData.get('date'),
                };

                if (submitter && submitter.name === 'sync') {
                    params.sync = 1;
                }

                loadAttendance(params);
            });

            document.addEventListener('click', function (event) {
                var link = event.target.closest('[data-attendance-date-link]');
                if (!link || link.classList.contains('pointer-events-none')) {
                    return;
                }

                event.preventDefault();

                var url = new URL(link.href, window.location.origin);
                var date = url.searchParams.get('date') || @json(now()->toDateString());

                loadAttendance({date: date});
            });

            window.addEventListener('popstate', function () {
                var url = new URL(window.location.href);
                var date = url.searchParams.get('date') || @json(now()->toDateString());

                loadAttendance({date: date}, {silent: true});
            });

            var editModal = document.getElementById('attendance-edit-modal');

            if (editModal) {
                var editForm = document.getElementById('attendance-edit-form');
                var editError = editModal.querySelector('[data-attendance-edit-error]');
                var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                function openEditModal(trigger) {
                    editForm.dataset.attendanceId = trigger.dataset.id;
                    editModal.querySelector('[data-attendance-edit-employee-name]').textContent = trigger.dataset.name || '';
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
                            check_out: document.getElementById('attendance-edit-check-out').value || null,
                            status: document.getElementById('attendance-edit-status').value,
                            remarks: document.getElementById('attendance-edit-remarks').value,
                        }),
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                if (!response.ok) {
                                    throw new Error(data.message || 'Could not update attendance.');
                                }

                                return data;
                            });
                        })
                        .then(function (data) {
                            closeEditModal();
                            showToast(data.message || 'Attendance record updated.');

                            var currentDate = document.getElementById('attendance-date');
                            loadAttendance({date: currentDate ? currentDate.value : null}, {silent: true});
                        })
                        .catch(function (err) {
                            editError.textContent = err.message;
                            editError.style.display = 'block';
                        });
                }

                document.addEventListener('click', function (event) {
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
        })();
    </script>
@endpush
