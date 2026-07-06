@extends('tyro-dashboard::layouts.admin')

@section('title', 'Employee Details')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('employees.index') }}">Employees</a>
<span class="breadcrumb-separator">/</span>
<span>{{ $employee->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">{{ $employee->name }}</h1>
            <p class="page-description">Review employee details and actions.</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Back to Employees</a>
        </div>
    </div>
</div>

<div class="card">
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
@endsection
