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
        <a href="{{ route('employees.index') }}" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Employees
        </a>
    </div>
</div>

<div class="card">
    <form action="{{ route('employees.update', $employee->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
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
                    <label for="designation" class="form-label">Designation</label>
                    <input type="text" id="designation" name="designation" class="form-input @error('designation') is-invalid @enderror" value="{{ old('designation', $employee->designation) }}">
                    @error('designation')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="job_title" class="form-label">Job Title</label>
                    <input type="text" id="job_title" name="job_title" class="form-input @error('job_title') is-invalid @enderror" value="{{ old('job_title', $employee->job_title) }}">
                    @error('job_title')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="job_join_date" class="form-label">Job Join Date</label>
                <input type="date" id="job_join_date" name="job_join_date" class="form-input @error('job_join_date') is-invalid @enderror" value="{{ old('job_join_date', $employee->job_join_date?->format('Y-m-d')) }}">
                @error('job_join_date')
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
        </div>

        <div class="card-footer" style="display: flex; gap: 0.75rem;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
