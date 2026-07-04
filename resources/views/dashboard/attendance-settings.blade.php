@extends('tyro-dashboard::layouts.admin')

@section('title', 'Attendance Settings')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Attendance Settings</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Settings</h1>
            <p class="page-description">Manage organization shifts, holidays, notifications, and audit visibility.</p>
        </div>
    </div>
</div>

{{-- Tab Navigation --}}
<div class="card" style="margin-bottom: 1rem; padding: 0.25rem;">
    <div style="display: flex; gap: 0.25rem; flex-wrap: nowrap; overflow-x: auto;">
        @php
            $tabs = [
                'general'                  => ['label' => 'General',                  'icon' => '<path d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7Z" stroke="currentColor" stroke-width="1.8"/><path d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a1.2 1.2 0 0 1 0 1.6l-1.2 1.2a1.2 1.2 0 0 1-1.6 0l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9V20a1.2 1.2 0 0 1-1.2 1.2h-1.7A1.2 1.2 0 0 1 11 20v-.2a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a1.2 1.2 0 0 1-1.6 0l-1.2-1.2a1.2 1.2 0 0 1 0-1.6l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6H4a1.2 1.2 0 0 1-1.2-1.2v-1.7A1.2 1.2 0 0 1 4 10h.2a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1l-.1-.1a1.2 1.2 0 0 1 0-1.6l1.2-1.2a1.2 1.2 0 0 1 1.6 0l.1.1a1 1 0 0 0 1.1.2 1 1 0 0 0 .6-.9V4A1.2 1.2 0 0 1 11 2.8h1.7A1.2 1.2 0 0 1 14 4v.2a1 1 0 0 0 .6.9 1 1 0 0 0 1.1-.2l.1-.1a1.2 1.2 0 0 1 1.6 0l1.2 1.2a1.2 1.2 0 0 1 0 1.6l-.1.1a1 1 0 0 0-.2 1.1 1 1 0 0 0 .9.6h.2a1.2 1.2 0 0 1 1.2 1.2v1.7a1.2 1.2 0 0 1-1.2 1.2h-.2a1 1 0 0 0-.9.6Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'],
                'shifts-and-schedule'      => ['label' => 'Shifts & Schedule',        'icon' => '<circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v4l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>'],
                'holiday-calendar'         => ['label' => 'Holiday Calendar',          'icon' => '<rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>'],
                'audit-logs'               => ['label' => 'Audit Logs',               'icon' => '<path d="M6 5h12M6 10h12M6 15h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="17" cy="15" r="2.5" stroke="currentColor" stroke-width="1.8"/>'],
                'notification-preferences' => ['label' => 'Notification Preferences', 'icon' => '<path d="M12 4a4 4 0 0 0-4 4v2.1c0 .8-.3 1.5-.8 2.1L6 14h12l-1.2-1.8a3.6 3.6 0 0 1-.8-2.1V8a4 4 0 0 0-4-4Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 17a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>'],
            ];
            $activeTab = request('tab', 'general');
            if (! array_key_exists($activeTab, $tabs)) {
                $activeTab = 'general';
            }
        @endphp

        @foreach($tabs as $key => $tab)
            <a href="{{ route('attendance-settings.index', ['tab' => $key]) }}"
               class="btn {{ $activeTab === $key ? 'btn-primary' : 'btn-ghost' }}"
               style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.875rem; white-space: nowrap;">
                <svg viewBox="0 0 24 24" style="width: 1rem; height: 1rem;" fill="none">{!! $tab['icon'] !!}</svg>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</div>

{{-- ======================= GENERAL TAB ======================= --}}
@if($activeTab === 'general')
<div class="card" style="margin-bottom: 1rem;">
    <form action="{{ route('attendance-settings.update') }}" method="POST">
        @csrf
        @method('PUT')
        {{-- Preserve backup fields so the shared update() validation passes --}}
        <input type="hidden" name="auto_backup_enabled" value="{{ $settings['auto_backup_enabled'] ? '1' : '0' }}">
        <input type="hidden" name="backup_frequency" value="{{ $settings['backup_frequency'] }}">
        <input type="hidden" name="backup_path" value="{{ $settings['backup_path'] }}">
        <div class="card-header">
            <h3 class="card-title">Company Information</h3>
            <p class="muted-text">Manage organization hours and weekend configuration.</p>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="working_hours_start" class="form-label">Working Hours Start</label>
                    <input type="time"
                           id="working_hours_start"
                           name="working_hours_start"
                           class="form-input @error('working_hours_start') is-invalid @enderror"
                           value="{{ old('working_hours_start', $settings['working_hours_start']) }}">
                    @error('working_hours_start')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="working_hours_end" class="form-label">Working Hours End</label>
                    <input type="time"
                           id="working_hours_end"
                           name="working_hours_end"
                           class="form-input @error('working_hours_end') is-invalid @enderror"
                           value="{{ old('working_hours_end', $settings['working_hours_end']) }}">
                    @error('working_hours_end')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Weekend Days</label>
                @php
                    $weekendOptions = [
                        'sat' => 'Sat', 'sun' => 'Sun', 'mon' => 'Mon',
                        'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri',
                    ];
                    $selectedWeekends = old('weekend_days', $settings['weekend_days']);
                @endphp
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.25rem;">
                    @foreach($weekendOptions as $value => $label)
                        <label style="display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; padding: 0.375rem 0.75rem; border: 1px solid var(--border); border-radius: 0.5rem; background: var(--surface-1); font-size: 0.875rem;">
                            <input type="checkbox"
                                   name="weekend_days[]"
                                   value="{{ $value }}"
                                   {{ in_array($value, (array) $selectedWeekends) ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('weekend_days')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="timezone" class="form-label">Timezone</label>
                <select id="timezone" name="timezone" class="form-input @error('timezone') is-invalid @enderror">
                    @foreach(['Asia/Dhaka', 'Asia/Kolkata', 'Asia/Karachi', 'UTC', 'Europe/London', 'America/New_York'] as $tz)
                        <option value="{{ $tz }}" {{ old('timezone', $settings['timezone']) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                    @endforeach
                </select>
                @error('timezone')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>
        </div>
        <div class="card-footer" style="display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

{{-- Backup Settings --}}
<div class="card">
    <form action="{{ route('attendance-settings.update') }}" method="POST" id="backupSettingsForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="working_hours_start" value="{{ $settings['working_hours_start'] }}">
        <input type="hidden" name="working_hours_end" value="{{ $settings['working_hours_end'] }}">
        @foreach($settings['weekend_days'] as $day)
            <input type="hidden" name="weekend_days[]" value="{{ $day }}">
        @endforeach
        <input type="hidden" name="timezone" value="{{ $settings['timezone'] }}">

        <div class="card-header">
            <h3 class="card-title">Backup Settings</h3>
            <p class="muted-text">Configure automatic backup preferences.</p>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label style="display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="hidden" name="auto_backup_enabled" value="0">
                    <input type="checkbox"
                           name="auto_backup_enabled"
                           value="1"
                           id="auto_backup_enabled"
                           {{ old('auto_backup_enabled', $settings['auto_backup_enabled']) ? 'checked' : '' }}
                           onchange="document.getElementById('backupFrequencyRow').style.display = this.checked ? '' : 'none'">
                    <span class="form-label" style="margin-bottom: 0;">Enable Automatic Backup</span>
                </label>
            </div>

            <div id="backupFrequencyRow" style="{{ $settings['auto_backup_enabled'] ? '' : 'display: none;' }}">
                <div class="form-row">
                    <div class="form-group">
                        <label for="backup_frequency" class="form-label">Backup Frequency</label>
                        <select id="backup_frequency" name="backup_frequency" class="form-input">
                            @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $value => $label)
                                <option value="{{ $value }}" {{ old('backup_frequency', $settings['backup_frequency']) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="backup_path" class="form-label">Backup Path</label>
                        <input type="text"
                               id="backup_path"
                               name="backup_path"
                               class="form-input"
                               value="{{ old('backup_path', $settings['backup_path']) }}"
                               placeholder="storage/app/backups">
                    </div>
                </div>
            </div>

            @if($settings['last_backup_at'])
                <p class="muted-text" style="font-size: 0.8125rem;">Last backup: {{ $settings['last_backup_at'] }}</p>
            @else
                <p class="muted-text" style="font-size: 0.8125rem;">No backups yet.</p>
            @endif
        </div>
        <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
            <form action="{{ route('attendance-settings.backup-now') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-secondary">Backup Now</button>
            </form>
            <button type="submit" form="backupSettingsForm" class="btn btn-primary">Save Backup Settings</button>
        </div>
    </form>
</div>
@endif

{{-- ======================= SHIFTS & SCHEDULE TAB ======================= --}}
@if($activeTab === 'shifts-and-schedule')
<div class="card">
    <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h3 class="card-title">Shifts & Schedule</h3>
            <p class="muted-text">Maintain working shift definitions for your teams.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openShiftModal()">Add Shift</button>
    </div>
    <div class="card-body">
        @if(count($shifts) > 0)
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Shift Name</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shifts as $shift)
                        <tr>
                            <td><strong>{{ $shift->name }}</strong></td>
                            <td>{{ $shift->start_time }}</td>
                            <td>{{ $shift->end_time }}</td>
                            <td style="text-align: right;">
                                <button type="button"
                                        class="btn btn-secondary btn-sm"
                                        onclick="openShiftModal({{ json_encode(['id' => $shift->id, 'name' => $shift->name, 'start_time' => $shift->start_time, 'end_time' => $shift->end_time]) }})">
                                    Edit
                                </button>
                                <button type="button"
                                        class="btn btn-danger btn-sm"
                                        onclick="confirmDeleteShift({{ $shift->id }})">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="padding: 2rem; text-align: center; color: var(--muted); border: 1px dashed var(--border); border-radius: 0.75rem;">
                No shifts configured yet. Click <strong>Add Shift</strong> to create one.
            </div>
        @endif
    </div>
</div>

{{-- Shift Modal --}}
<div id="shiftModal" style="display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,0.45); align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 1rem; width: 100%; max-width: 28rem; box-shadow: 0 20px 40px rgba(255, 255, 255, 0.2);">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
            <h3 id="shiftModalTitle" style="font-size: 1rem; font-weight: 600; margin: 0;">Create Shift</h3>
            <button type="button" onclick="closeShiftModal()" style="background: none; border: none; cursor: pointer; color: var(--muted);">
                <svg viewBox="0 0 24 24" style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="shiftForm" method="POST" style="padding: 1.25rem 1.5rem 1.5rem;">
            @csrf
            <input type="hidden" name="_method" id="shiftMethod" value="POST">
            <input type="hidden" name="id" id="shiftId">

            <div class="form-group">
                <label for="shiftName" class="form-label">Shift Name</label>
                <input type="text" id="shiftName" name="name" class="form-input" placeholder="Morning Shift" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="shiftStart" class="form-label">Start Time</label>
                    <input type="time" id="shiftStart" name="start_time" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="shiftEnd" class="form-label">End Time</label>
                    <input type="time" id="shiftEnd" name="end_time" class="form-input" required>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.5rem;">
                <button type="button" onclick="closeShiftModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Shift</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ======================= HOLIDAY CALENDAR TAB ======================= --}}
@if($activeTab === 'holiday-calendar')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Holiday Calendar</h3>
    </div>
    <div class="card-body">
        <p class="muted-text">Coming soon. This section will be updated with holiday calendar management.</p>
    </div>
</div>
@endif

{{-- ======================= AUDIT LOGS TAB ======================= --}}
@if($activeTab === 'audit-logs')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Audit Logs</h3>
    </div>
    <div class="card-body">
        <p class="muted-text">Coming soon. This section will be updated with detailed attendance audit records.</p>
    </div>
</div>
@endif

{{-- ======================= NOTIFICATION PREFERENCES TAB ======================= --}}
@if($activeTab === 'notification-preferences')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Notification Preferences</h3>
    </div>
    <div class="card-body">
        <p class="muted-text">Coming soon. This section will be updated with notification rules and delivery settings.</p>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    function openShiftModal(shift) {
        const modal = document.getElementById('shiftModal');
        const title = document.getElementById('shiftModalTitle');
        const form  = document.getElementById('shiftForm');
        const method = document.getElementById('shiftMethod');

        if (shift) {
            title.textContent = 'Edit Shift';
            document.getElementById('shiftId').value        = shift.id;
            document.getElementById('shiftName').value      = shift.name;
            document.getElementById('shiftStart').value     = shift.start_time;
            document.getElementById('shiftEnd').value       = shift.end_time;
            method.value = 'PUT';
            form.action  = '/attendance-settings/shifts/' + shift.id;
        } else {
            title.textContent = 'Create Shift';
            document.getElementById('shiftId').value    = '';
            document.getElementById('shiftName').value  = '';
            document.getElementById('shiftStart').value = '09:00';
            document.getElementById('shiftEnd').value   = '18:00';
            method.value = 'POST';
            form.action  = '/attendance-settings/shifts';
        }

        modal.style.display = 'flex';
    }

    function closeShiftModal() {
        document.getElementById('shiftModal').style.display = 'none';
    }

    function confirmDeleteShift(id) {
        showDanger('Delete Shift', 'Are you sure you want to delete this shift? This action cannot be undone.').then(function (confirmed) {
            if (! confirmed) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/attendance-settings/shifts/' + id;
            form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        });
    }
</script>
@endpush
