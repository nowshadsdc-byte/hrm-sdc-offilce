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
            <h1 class="page-title" style="background: linear-gradient(135deg, #1e40af, #2563eb); color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; display: inline-block;">Employee Directory</h1>
            <p class="page-description" style="margin-top: 0.5rem;">Manage your workforce</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button type="button" onclick="exportCSV()" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; border: 1px solid #d1d5db; background: white; border-radius: 0.5rem; cursor: pointer; font-weight: 500; color: #000;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Export CSV
            </button>
            <button type="button" onclick="openAddEmployeeModal()" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; background: #10b981; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Employee
            </button>
        </div>
    </div>
</div>

{{-- Search & Filter Section --}}
<div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem;">
    <form action="{{ route('employees.index') }}" method="GET" style="display: flex; flex-direction: column; gap: 1rem;">
        {{-- Search Input --}}
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div style="flex: 1; position: relative;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: #9ca3af; pointer-events: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" placeholder="Search name, email, ID..." value="{{ request('search') }}" style="width: 100%; padding: 0.625rem 0.75rem 0.625rem 2.5rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem;">
            </div>
        </div>

        {{-- Filters --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <select name="department" style="padding: 0.625rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; background: white;">
                <option value="">All Departments</option>
                <option value="engineering" {{ request('department') === 'engineering' ? 'selected' : '' }}>Engineering</option>
                <option value="operations" {{ request('department') === 'operations' ? 'selected' : '' }}>Operations</option>
                <option value="finance" {{ request('department') === 'finance' ? 'selected' : '' }}>Finance</option>
                <option value="sales" {{ request('department') === 'sales' ? 'selected' : '' }}>Sales & Marketing</option>
                <option value="hr" {{ request('department') === 'hr' ? 'selected' : '' }}>Human Resources</option>
                <option value="customer_support" {{ request('department') === 'customer_support' ? 'selected' : '' }}>Customer Support</option>
                <option value="product" {{ request('department') === 'product' ? 'selected' : '' }}>Product</option>
            </select>

            <select name="status" style="padding: 0.625rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; background: white;">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="on_leave" {{ request('status') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
            </select>

            <select name="role" style="padding: 0.625rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; background: white;">
                <option value="">All Roles</option>
                <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="manager" {{ request('role') === 'manager' ? 'selected' : '' }}>Manager</option>
                <option value="employee" {{ request('role') === 'employee' ? 'selected' : '' }}>Employee</option>
            </select>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center;">
            <button type="submit" style="padding: 0.625rem 1rem; background: #10b981; color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">Search</button>
            @if(request()->filled('search') || request()->filled('department') || request()->filled('status') || request()->filled('role'))
                <a href="{{ route('employees.index') }}" style="padding: 0.625rem 1rem; background: #f3f4f6; color: #000; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; text-decoration: none;">Clear Filters</a>
            @endif
            <span style="font-size: 0.875rem; color: #6b7280;">Showing {{ $employees->count() }} of {{ $employees->total() }} employees</span>
        </div>
    </form>
</div>

{{-- Employees Table --}}
<div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                    <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: #6b7280; text-transform: uppercase;">Employee</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: #6b7280; text-transform: uppercase;">Emp ID</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: #6b7280; text-transform: uppercase;">Department</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: #6b7280; text-transform: uppercase;">Designation</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: #6b7280; text-transform: uppercase;">Role</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: #6b7280; text-transform: uppercase;">Device</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: #6b7280; text-transform: uppercase;">Status</th>
                    <th style="padding: 1rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: #6b7280; text-transform: uppercase;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $employee)
                <tr style="border-bottom: 1px solid #e5e7eb; transition: background 0.2s;">
                    <td style="padding: 1rem; color: #000;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; flex-shrink: 0;">
                                {{ substr($employee->name, 0, 2) }}
                            </div>
                            <div>
                                <p style="margin: 0; font-weight: 600; color: #000;">{{ $employee->name }}</p>
                                <p style="margin: 0; font-size: 0.75rem; color: #6b7280;">{{ $employee->user->email ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 1rem; color: #1e40af; font-weight: 600;">EMP-{{ str_pad($employee->id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td style="padding: 1rem; color: #000;">{{ $employee->department ?? 'N/A' }}</td>
                    <td style="padding: 1rem; color: #000;">{{ $employee->designation ?? 'N/A' }}</td>
                    <td style="padding: 1rem;">
                        @if($employee->role)
                            <span style="display: inline-block; padding: 0.375rem 0.75rem; background: #ecfeff; color: #0f766e; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600;">
                                {{ ucfirst(str_replace('_', ' ', $employee->role)) }}
                            </span>
                        @elseif($employee->user?->roles?->first())
                            <span style="display: inline-block; padding: 0.375rem 0.75rem; background: #e0e7ff; color: #4f46e5; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600;">
                                {{ ucfirst(str_replace('_', ' ', $employee->user->roles->first()->name)) }}
                            </span>
                        @else
                            <span style="color: #6b7280;">N/A</span>
                        @endif
                    </td>
                    <td style="padding: 1rem; color: #000;">
                        @php
                            $latestAttendance = $employee->attendances?->sortByDesc('created_at')->first();
                        @endphp
                        @if($latestAttendance?->device)
                            {{ $latestAttendance->device->name ?? 'N/A' }}
                        @else
                            <span style="color: #9ca3af;">Not assigned</span>
                        @endif
                    </td>
                    <td style="padding: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            @if($employee->status === 'active')
                                <span style="display: inline-block; width: 0.5rem; height: 0.5rem; border-radius: 50%; background: #10b981;"></span>
                                <span style="font-size: 0.875rem; font-weight: 600; color: #10b981;">Active</span>
                            @else
                                <span style="display: inline-block; width: 0.5rem; height: 0.5rem; border-radius: 50%; background: #d1d5db;"></span>
                                <span style="font-size: 0.875rem; font-weight: 600; color: #6b7280;">Inactive</span>
                            @endif
                            {{-- Toggle Switch --}}
                            <label style="position: relative; cursor: pointer; margin-left: auto;">
                                <input type="checkbox" {{ $employee->status === 'active' ? 'checked' : '' }} onchange="toggleEmployeeStatus({{ $employee->id }}, this.checked)" style="display: none;">
                                <div style="width: 2rem; height: 1rem; background: {{ $employee->status === 'active' ? '#10b981' : '#d1d5db' }}; border-radius: 0.5rem; position: relative; transition: all 0.3s;">
                                    <div style="position: absolute; top: 0.125rem; {{ $employee->status === 'active' ? 'right' : 'left' }}: 0.125rem; width: 0.75rem; height: 0.75rem; background: white; border-radius: 50%; transition: all 0.3s;"></div>
                                </div>
                            </label>
                        </div>
                    </td>
                    <td style="padding: 1rem; text-align: center;">
                        <div style="display: flex; justify-content: center; gap: 0.5rem;">
                            <a href="{{ route('employees.edit', $employee) }}" title="Edit Employee" style="padding: 0.5rem 0.75rem; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; text-decoration: none;">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Are you sure you want to delete this employee?');" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Delete Employee" style="padding: 0.5rem 0.75rem; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; cursor: pointer;">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="padding: 3rem 1rem; text-align: center; color: #6b7280;">
                        <p style="font-weight: 600;">No employees found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($employees->hasPages())
    <div style="padding: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div style="font-size: 0.875rem; color: #6b7280;">
            Page {{ $employees->currentPage() }} of {{ $employees->lastPage() }} • {{ $employees->total() }} employees
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            @if($employees->onFirstPage())
                <button disabled style="padding: 0.5rem 0.75rem; background: #f3f4f6; color: #d1d5db; border: 1px solid #e5e7eb; border-radius: 0.375rem; cursor: not-allowed; font-weight: 500;">← Prev</button>
            @else
                <a href="{{ $employees->previousPageUrl() }}" style="padding: 0.5rem 0.75rem; background: white; color: #000; border: 1px solid #d1d5db; border-radius: 0.375rem; cursor: pointer; font-weight: 500; text-decoration: none;">← Prev</a>
            @endif

            @foreach($employees->getUrlRange(1, $employees->lastPage()) as $page => $url)
                @if($page == $employees->currentPage())
                    <button style="padding: 0.5rem 0.75rem; background: #10b981; color: white; border: 1px solid #10b981; border-radius: 0.375rem; font-weight: 600;">{{ $page }}</button>
                @else
                    <a href="{{ $url }}" style="padding: 0.5rem 0.75rem; background: white; color: #000; border: 1px solid #d1d5db; border-radius: 0.375rem; cursor: pointer; font-weight: 500; text-decoration: none;">{{ $page }}</a>
                @endif
            @endforeach

            @if($employees->hasMorePages())
                <a href="{{ $employees->nextPageUrl() }}" style="padding: 0.5rem 0.75rem; background: white; color: #000; border: 1px solid #d1d5db; border-radius: 0.375rem; cursor: pointer; font-weight: 500; text-decoration: none;">Next →</a>
            @else
                <button disabled style="padding: 0.5rem 0.75rem; background: #f3f4f6; color: #d1d5db; border: 1px solid #e5e7eb; border-radius: 0.375rem; cursor: not-allowed; font-weight: 500;">Next →</button>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- Add Employee Modal --}}
<div id="addEmployeeModal" style="display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,0.45); align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 0.75rem; width: 100%; max-width: 32rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); color: #000; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; background: white; display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 style="font-size: 1rem; font-weight: 600; margin: 0; color: #000;">Add New Employee</h3>
                <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0;">Create a new employee record in the system.</p>
            </div>
            <button type="button" onclick="closeAddEmployeeModal()" style="background: none; border: none; font-size: 1.5rem; color: #6b7280; cursor: pointer; padding: 0; width: 1.5rem; height: 1.5rem; display: flex; align-items: center; justify-content: center;">×</button>
        </div>

        <form method="POST" action="{{ route('employees.store') }}" style="padding: 1.5rem;">
            @csrf
            <div style="display: grid; gap: 1rem;">
                <div>
                    <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Linked User (relation)</label>
                    <select name="user_id" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem; background: white;">
                        <option value="">No linked user</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Employee Name <span style="color: #10b981;">*</span></label>
                    <input type="text" name="name" required style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">NID</label>
                        <input type="text" name="nid" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Phone</label>
                        <input type="text" name="phone" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Date of Birth</label>
                        <input type="date" name="dob" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Job Join Date</label>
                        <input type="date" name="job_join_date" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Address</label>
                    <textarea name="address" rows="2" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Device User ID</label>
                        <input type="text" name="device_user_id" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Device Card No</label>
                        <input type="text" name="device_cardno" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Department</label>
                        <input type="text" name="department" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Designation</label>
                        <input type="text" name="designation" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Job Title</label>
                        <input type="text" name="job_title" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Role</label>
                        <input type="text" name="role" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Job Description</label>
                    <textarea name="job_description" rows="2" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">NID File Path</label>
                        <input type="text" name="nid_file" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Certificate File Path</label>
                        <input type="text" name="certificate_file" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Contract File Path</label>
                        <input type="text" name="contract_file" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Status <span style="color: #10b981;">*</span></label>
                        <select name="status" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem; background: white;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="separated">Separated</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Separation Type</label>
                        <input type="text" name="separation_type" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Separation Date</label>
                        <input type="date" name="separation_date" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Clearance Completed <span style="color: #10b981;">*</span></label>
                        <select name="clearance_completed" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem; background: white;">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; color: #000; margin-bottom: 0.5rem;">Transfer/Promotion Notes</label>
                    <textarea name="transfer_promotion_notes" rows="2" style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem;"></textarea>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeAddEmployeeModal()" style="padding: 0.625rem 1.25rem; border: 1px solid #d1d5db; background: white; color: #000; border-radius: 0.5rem; font-weight: 500; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 0.625rem 1.5rem; background: #10b981; color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">Add Employee</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('styles')
<style>
    #addEmployeeModal input:focus,
    #addEmployeeModal select:focus {
        outline: none;
        border-color: #10b981 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    tr:hover {
        background: #f9fafb !important;
    }
</style>
@endpush

@push('scripts')
<script>
    function openAddEmployeeModal() {
        document.getElementById('addEmployeeModal').style.display = 'flex';
    }

    function closeAddEmployeeModal() {
        document.getElementById('addEmployeeModal').style.display = 'none';
    }

    function toggleEmployeeStatus(employeeId, isActive) {
        // You can add AJAX call here to update status
        console.log('Toggle status for employee', employeeId, 'to', isActive);
    }

    function exportCSV() {
        const table = document.querySelector('table');
        let csv = [];
        let rows = table.querySelectorAll('tr');

        rows.forEach(row => {
            let rowData = [];
            row.querySelectorAll('td, th').forEach(cell => {
                rowData.push('"' + cell.innerText.replace(/"/g, '""') + '"');
            });
            csv.push(rowData.join(','));
        });

        const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
        const link = document.createElement('a');
        link.setAttribute('href', encodeURI(csvContent));
        link.setAttribute('download', 'employees.csv');
        link.click();
    }

    document.getElementById('addEmployeeModal').addEventListener('click', function (e) {
        if (e.target === this) closeAddEmployeeModal();
    });
</script>
@endpush
