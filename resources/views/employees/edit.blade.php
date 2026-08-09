@extends('tyro-dashboard::layouts.admin')

@section('title', 'Edit Employee')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('employees.index') }}">Employees</a>
<span class="breadcrumb-separator">/</span>
<span>Edit</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Edit Employee</h1>
            <p class="page-description">Update details for {{ $employee->name }}.</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            @if ($canManageDocuments)
                <a href="{{ route('employees.show', $employee) }}#documents" class="btn btn-secondary" onclick="sessionStorage.setItem('employeeProfileTab', 'documents')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5a1 1 0 0 0 1 1h5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z" />
                    </svg>
                    Documents
                </a>
            @endif
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Employees
            </a>
        </div>
    </div>
</div>

<div class="card">
    <form action="{{ route('employees.update', $employee->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label for="user_id" class="form-label">Linked User (relation)</label>
                <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                    <option value="">No linked user</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ (string) old('user_id', $employee->user_id) === (string) $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" id="name" name="name" class="form-input @error('name') is-invalid @enderror" value="{{ old('name', $employee->name) }}" required>
                    @error('name')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="nid" class="form-label">NID</label>
                    <input type="text" id="nid" name="nid" class="form-input @error('nid') is-invalid @enderror" value="{{ old('nid', $employee->nid) }}">
                    @error('nid')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" id="phone" name="phone" class="form-input @error('phone') is-invalid @enderror" value="{{ old('phone', $employee->phone) }}">
                    @error('phone')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="department" class="form-label">Department</label>
                    <input type="text" id="department" name="department" class="form-input @error('department') is-invalid @enderror" value="{{ old('department', $employee->department) }}">
                    @error('department')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="device_user_id" class="form-label">Map Device ID (Device User ID)</label>
                    <input type="text" id="device_user_id" name="device_user_id" class="form-input @error('device_user_id') is-invalid @enderror" value="{{ old('device_user_id', $employee->device_user_id) }}">
                    @error('device_user_id')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="device_cardno" class="form-label">Device Card No</label>
                    <input type="text" id="device_cardno" name="device_cardno" class="form-input @error('device_cardno') is-invalid @enderror" value="{{ old('device_cardno', $employee->device_cardno) }}">
                    @error('device_cardno')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="designation" class="form-label">Designation</label>
                    <input type="text" id="designation" name="designation" class="form-input @error('designation') is-invalid @enderror" value="{{ old('designation', $employee->designation) }}">
                    @error('designation')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="role" class="form-label">Role</label>
                    <input type="text" id="role" name="role" class="form-input @error('role') is-invalid @enderror" value="{{ old('role', $employee->role) }}">
                    @error('role')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="job_title" class="form-label">Job Title</label>
                <input type="text" id="job_title" name="job_title" class="form-input @error('job_title') is-invalid @enderror" value="{{ old('job_title', $employee->job_title) }}">
                @error('job_title')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="dob" class="form-label">Date of Birth</label>
                    <input type="date" id="dob" name="dob" class="form-input @error('dob') is-invalid @enderror" value="{{ old('dob', $employee->dob?->format('Y-m-d')) }}">
                    @error('dob')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="job_join_date" class="form-label">Job Join Date</label>
                    <input type="date" id="job_join_date" name="job_join_date" class="form-input @error('job_join_date') is-invalid @enderror" value="{{ old('job_join_date', $employee->job_join_date?->format('Y-m-d')) }}">
                    @error('job_join_date')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="job_description" class="form-label">Job Description</label>
                <textarea id="job_description" name="job_description" class="form-input @error('job_description') is-invalid @enderror" rows="3">{{ old('job_description', $employee->job_description) }}</textarea>
                @error('job_description')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="address" class="form-label">Address</label>
                <textarea id="address" name="address" class="form-input @error('address') is-invalid @enderror" rows="4">{{ old('address', $employee->address) }}</textarea>
                @error('address')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="active" {{ old('status', $employee->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $employee->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="separated" {{ old('status', $employee->status) === 'separated' ? 'selected' : '' }}>Separated</option>
                    </select>
                    @error('status')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="clearance_completed" class="form-label">Clearance Completed</label>
                    <select id="clearance_completed" name="clearance_completed" class="form-select @error('clearance_completed') is-invalid @enderror">
                        <option value="0" {{ old('clearance_completed', $employee->clearance_completed) ? '' : 'selected' }}>No</option>
                        <option value="1" {{ old('clearance_completed', $employee->clearance_completed) ? 'selected' : '' }}>Yes</option>
                    </select>
                    @error('clearance_completed')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="separation_type" class="form-label">Separation Type</label>
                    <input type="text" id="separation_type" name="separation_type" class="form-input @error('separation_type') is-invalid @enderror" value="{{ old('separation_type', $employee->separation_type) }}">
                    @error('separation_type')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="separation_date" class="form-label">Separation Date</label>
                    <input type="date" id="separation_date" name="separation_date" class="form-input @error('separation_date') is-invalid @enderror" value="{{ old('separation_date', $employee->separation_date?->format('Y-m-d')) }}">
                    @error('separation_date')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="transfer_promotion_notes" class="form-label">Transfer/Promotion Notes</label>
                <textarea id="transfer_promotion_notes" name="transfer_promotion_notes" class="form-input @error('transfer_promotion_notes') is-invalid @enderror" rows="3">{{ old('transfer_promotion_notes', $employee->transfer_promotion_notes) }}</textarea>
                @error('transfer_promotion_notes')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="nid_file" class="form-label">NID File Path</label>
                    <input type="text" id="nid_file" name="nid_file" class="form-input @error('nid_file') is-invalid @enderror" value="{{ old('nid_file', $employee->nid_file) }}">
                    @error('nid_file')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="certificate_file" class="form-label">Certificate File Path</label>
                    <input type="text" id="certificate_file" name="certificate_file" class="form-input @error('certificate_file') is-invalid @enderror" value="{{ old('certificate_file', $employee->certificate_file) }}">
                    @error('certificate_file')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="contract_file" class="form-label">Contract File Path</label>
                <input type="text" id="contract_file" name="contract_file" class="form-input @error('contract_file') is-invalid @enderror" value="{{ old('contract_file', $employee->contract_file) }}">
                @error('contract_file')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        @include('employees.partials.shift-schedule', ['shifts' => $shifts, 'selectedShiftId' => $employee->shift_id])
        @include('employees.partials.leave-allocation', ['isAdmin' => $isAdmin, 'selectedAnnualLeaveDays' => $employee->annual_leave_days, 'defaultAnnualLeaveDays' => $defaultAnnualLeaveDays])

        <div class="card-footer" style="display: flex; gap: 0.75rem;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
