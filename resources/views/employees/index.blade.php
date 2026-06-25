@extends('tyro-dashboard::layouts.admin')

@section('title', 'Employees')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Employees</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Employees</h1>
            <p class="page-description">View, manage, and create employee records from the dashboard.</p>
        </div>
        <a href="{{ route('employees.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Employee
        </a>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('employees.index') }}" method="GET">
            <div class="filters-bar">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="search" class="form-input" placeholder="Search employees..." value="{{ request('search') }}">
                </div>
                <button type="submit" class="btn btn-secondary">Search</button>
                @if(request()->filled('search'))
                    <a href="{{ route('employees.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($employees->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>NID</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Status</th>
                        <th style="width: 170px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $employee)
                    <tr>
                        <td>
                            <a href="{{ route('employees.show', $employee->id) }}" style="text-decoration: none; color: inherit;">
                                <strong>{{ $employee->name }}</strong>
                                <div class="muted-text">Joined {{ optional($employee->created_at)->format('M d, Y') }}</div>
                            </a>
                        </td>
                        <td>{{ $employee->nid ?? '—' }}</td>
                        <td>{{ $employee->phone ?? '—' }}</td>
                        <td>{{ $employee->department ?? '—' }}</td>
                        <td>{{ $employee->designation ?? '—' }}</td>
                        <td>
                            @if($employee->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-secondary">{{ ucwords(str_replace('_', ' ', $employee->status)) }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end; gap: 0.5rem;">
                                <a href="{{ route('employees.show', $employee->id) }}" class="action-btn" title="View">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <a href="{{ route('employees.edit', $employee->id) }}" class="action-btn" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.414 2.586a2 2 0 112.828 2.828L12.828 14.828 9 15l.172-3.828L18.414 2.586z" />
                                    </svg>
                                </a>
                                <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" style="display: inline;" id="delete-employee-form-{{ $employee->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-danger" title="Delete" onclick="event.preventDefault(); showDanger('Delete Employee', 'Are you sure you want to delete {{ addslashes($employee->name) }}? This action cannot be undone.').then(confirmed => { if (confirmed) document.getElementById('delete-employee-form-{{ $employee->id }}').submit(); })">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())
        <div class="pagination">
            {{ $employees->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h3 class="empty-state-title">No employees found</h3>
            <p class="empty-state-description">Create a new employee record to get started.</p>
            <a href="{{ route('employees.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add Employee
            </a>
        </div>
    @endif
</div>
@endsection
