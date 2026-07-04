@extends('tyro-dashboard::layouts.admin')

@section('title', 'Leave Requests')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Leave Requests</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Leave Requests</h1>
            <p class="page-description">Manage employee leave requests and approvals.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openApplyLeaveModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Apply Leave
        </button>
    </div>
</div>

{{-- Statistics Cards --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="border-left: 4px solid var(--warning);">
        <div class="card-body">
            <p class="muted-text" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Pending Requests</p>
            <p style="font-size: 2rem; font-weight: 700; color: var(--warning); margin-top: 0.5rem;">{{ $stats['pending'] }}</p>
        </div>
    </div>

    <div class="card" style="border-left: 4px solid var(--success);">
        <div class="card-body">
            <p class="muted-text" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Approved</p>
            <p style="font-size: 2rem; font-weight: 700; color: var(--success); margin-top: 0.5rem;">{{ $stats['approved'] }}</p>
        </div>
    </div>

    <div class="card" style="border-left: 4px solid var(--danger);">
        <div class="card-body">
            <p class="muted-text" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Rejected</p>
            <p style="font-size: 2rem; font-weight: 700; color: var(--danger); margin-top: 0.5rem;">{{ $stats['rejected'] }}</p>
        </div>
    </div>
</div>

{{-- Leave Requests List --}}
<div class="card">
    <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <a href="{{ route('leave-requests.index', ['tab' => 'all']) }}" class="btn leave-req-tab-btn {{ $tab === 'all' ? 'active' : '' }}">All Requests</a>
            <a href="{{ route('leave-requests.index', ['tab' => 'pending']) }}" class="btn leave-req-tab-btn {{ $tab === 'pending' ? 'active' : '' }}">Pending</a>
            <a href="{{ route('leave-requests.index', ['tab' => 'approved']) }}" class="btn leave-req-tab-btn {{ $tab === 'approved' ? 'active' : '' }}">Approved</a>
            <a href="{{ route('leave-requests.index', ['tab' => 'rejected']) }}" class="btn leave-req-tab-btn {{ $tab === 'rejected' ? 'active' : '' }}">Rejected</a>
        </div>
        <p class="muted-text">{{ count($leaveRequests) }} requests</p>
    </div>

    <div style="padding: 1.5rem;">
        @if(count($leaveRequests) > 0)
            <div style="display: grid; gap: 1rem;">
                @foreach($leaveRequests as $request)
                <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; background: white; display: grid; grid-template-columns: auto 1fr auto; gap: 1.5rem; align-items: start; color: #000;">
                    {{-- Employee Avatar & Info --}}
                    <div style="display: flex; flex-direction: column; align-items: center; text-align: center; min-width: 80px;">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.25rem; margin-bottom: 0.5rem;">
                            {{ strtoupper(substr($request->employee->name, 0, 2)) }}
                        </div>
                        <p style="font-weight: 600; font-size: 0.875rem; margin: 0; line-height: 1.3; color: #000;">{{ $request->employee->name }}</p>
                        @if($request->employee->user)
                            <p style="font-size: 0.75rem; color: #6b7280; margin: 0.25rem 0 0;">{{ $request->employee->user->email ?? 'N/A' }}</p>
                        @endif
                    </div>

                    {{-- Leave Details --}}
                    <div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                            {{-- Leave Type Badge --}}
                            <div>
                                <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: #6b7280; margin: 0 0 0.25rem;">Leave Type</p>
                                <div style="display: inline-block; padding: 0.375rem 0.75rem; background: #dbeafe; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #1e40af;">
                                    {{ $request->leave_type }}
                                </div>
                            </div>

                            {{-- Status Badge --}}
                            <div>
                                <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: #6b7280; margin: 0 0 0.25rem;">Status</p>
                                <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.375rem 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600;
                                    @if($request->status === 'pending')
                                        background: rgba(229, 131, 16, 0.1);
                                        color: var(--warning);
                                    @elseif($request->status === 'approved')
                                        background: var(--success-50);
                                        color: var(--success);
                                    @else
                                        background: var(--danger-50);
                                        color: var(--danger);
                                    @endif
                                ">
                                    @if($request->status === 'pending')
                                        <svg viewBox="0 0 24 24" fill="currentColor" style="width: 1rem; height: 1rem;">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/>
                                        </svg>
                                    @elseif($request->status === 'approved')
                                        <svg viewBox="0 0 24 24" fill="currentColor" style="width: 1rem; height: 1rem;">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                        </svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="currentColor" style="width: 1rem; height: 1rem;">
                                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                                        </svg>
                                    @endif
                                    {{ ucfirst($request->status) }}
                                </div>
                            </div>

                            {{-- Duration --}}
                            <div>
                                <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: #6b7280; margin: 0 0 0.25rem;">Duration</p>
                                <p style="font-size: 0.875rem; font-weight: 600; margin: 0; color: #000;">{{ $request->days_count }} day{{ $request->days_count > 1 ? 's' : '' }}</p>
                            </div>
                        </div>

                        {{-- Dates --}}
                        <div style="display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding: 0.75rem; background: #f3f4f6; border-radius: 0.5rem;">
                            <p style="font-size: 0.875rem; font-weight: 600; margin: 0; color: #000;">{{ \Carbon\Carbon::parse($request->start_date)->format('M d, Y') }}</p>
                            <div style="height: 2px; background: #e5e7eb; border-radius: 1px;"></div>
                            <p style="font-size: 0.875rem; font-weight: 600; margin: 0; color: #000;">{{ \Carbon\Carbon::parse($request->end_date)->format('M d, Y') }}</p>
                        </div>

                        {{-- Department --}}
                        <p style="font-size: 0.875rem; color: #374151; margin: 0.5rem 0 0;">
                            <strong style="color: #000;">Department:</strong> {{ $request->employee->department ?? 'Not Assigned' }}
                        </p>

                        {{-- Reason --}}
                        @if($request->reason)
                        <p style="font-size: 0.875rem; color: #374151; margin: 0.5rem 0 0;">
                            <strong style="color: #000;">Reason:</strong> {{ $request->reason }}
                        </p>
                        @endif

                        {{-- Rejection Reason --}}
                        @if($request->rejection_reason)
                        <div style="margin-top: 0.75rem; padding: 0.75rem; background: var(--danger-50); border-left: 3px solid var(--danger); border-radius: 0.375rem;">
                            <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: var(--danger); margin: 0 0 0.25rem;">Rejection Reason</p>
                            <p style="font-size: 0.875rem; color: var(--danger-900); margin: 0;">{{ $request->rejection_reason }}</p>
                        </div>
                        @endif

                        {{-- Applied timestamp --}}
                        <p style="font-size: 0.75rem; color: #6b7280; margin: 0.75rem 0 0; display: flex; align-items: center; gap: 0.375rem;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 0.875rem; height: 0.875rem;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Applied {{ $request->created_at->diffForHumans() }}
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; min-width: 120px;">
                        @if($request->status === 'pending')
                            <button type="button" class="btn btn-success btn-sm" onclick="openApproveModal({{ $request->id }})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 0.875rem; height: 0.875rem;">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                Approve
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="openRejectModal({{ $request->id }})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 0.875rem; height: 0.875rem;">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                                Reject
                            </button>
                        @else
                            <button type="button" class="btn btn-secondary btn-sm" onclick="confirmDelete({{ $request->id }})" style="opacity: 0.6;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 0.875rem; height: 0.875rem;">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                                Delete
                            </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div style="padding: 3rem 1rem; text-align: center; color: var(--muted);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 3rem; height: 3rem; margin: 0 auto 1rem; opacity: 0.5;">
                    <path d="M8 6h12M8 12h12M8 18h12M3 6h.01M3 12h.01M3 18h.01"></path>
                </svg>
                <p style="font-size: 1rem; font-weight: 500;">No leave requests found.</p>
            </div>
        @endif
    </div>
</div>

{{-- Apply Leave Modal --}}
<div id="applyLeaveModal" style="display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,0.45); align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 1rem; width: 100%; max-width: 40rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); color: #000;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);">
            <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Apply for Leave</h3>
        </div>

        <form id="applyLeaveForm" method="POST" action="{{ route('leave-requests.store') }}" style="padding: 1.25rem 1.5rem 1.5rem;">
            @csrf
            <div class="form-group">
                <label for="leaveEmployee" class="form-label">Employee</label>
                <select id="leaveEmployee" name="employee_id" class="form-input" required>
                    <option value="">Select employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="leaveType" class="form-label">Leave Type</label>
                <select id="leaveType" name="leave_type" class="form-input" required>
                    <option value="">Select leave type</option>
                    @foreach($leaveTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="startDate" class="form-label">Start Date</label>
                    <input type="date" id="startDate" name="start_date" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="endDate" class="form-label">End Date</label>
                    <input type="date" id="endDate" name="end_date" class="form-input" required>
                </div>
            </div>

            <div class="form-group">
                <label for="leaveReason" class="form-label">Reason</label>
                <textarea id="leaveReason" name="reason" class="form-input" rows="3" placeholder="Optional reason for the leave request"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                <button type="button" onclick="closeApplyLeaveModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>

{{-- Approve Modal --}}
<div id="approveModal" style="display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,0.45); align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 1rem; width: 100%; max-width: 28rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); color: #000;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);">
            <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Approve Leave Request</h3>
        </div>

        <div style="padding: 1.25rem 1.5rem;">
            <p style="color: var(--muted); margin: 0 0 1rem;">Are you sure you want to approve this leave request?</p>

            <form id="approveForm" method="POST" style="display: none;">
                @csrf
                @method('PUT')
            </form>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" onclick="closeApproveModal()" class="btn btn-secondary">Cancel</button>
                <button type="button" onclick="document.getElementById('approveForm').submit();" class="btn btn-success">Approve</button>
            </div>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" style="display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,0.45); align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 1rem; width: 100%; max-width: 28rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); color: #000;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);">
            <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Reject Leave Request</h3>
        </div>

        <form id="rejectForm" method="POST" style="padding: 1.25rem 1.5rem 1.5rem;">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="rejectionReason" class="form-label">Rejection Reason</label>
                <textarea id="rejectionReason" name="rejection_reason" class="form-input" rows="3" placeholder="Explain why this request is being rejected" required></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" onclick="closeRejectModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject</button>
            </div>
        </form>
    </div>
</div>

<style>
    .leave-req-tab-btn {
        padding: 0.4rem 1rem;
        font-size: 0.875rem;
        border: 1px solid var(--border);
        background: var(--surface-1);
        color: var(--text);
        border-radius: 0.375rem;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
    }

    .leave-req-tab-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .leave-req-tab-btn:hover:not(.active) {
        background: var(--surface-2);
    }
</style>

@endsection

@push('scripts')
<script>
    function openApplyLeaveModal() {
        document.getElementById('applyLeaveModal').style.display = 'flex';
        document.getElementById('leaveEmployee').focus();
    }

    function closeApplyLeaveModal() {
        document.getElementById('applyLeaveModal').style.display = 'none';
        document.getElementById('applyLeaveForm').reset();
    }

    function openApproveModal(leaveRequestId) {
        const form = document.getElementById('approveForm');
        form.action = `/leave-requests/${leaveRequestId}/approve`;
        document.getElementById('approveModal').style.display = 'flex';
    }

    function closeApproveModal() {
        document.getElementById('approveModal').style.display = 'none';
    }

    function openRejectModal(leaveRequestId) {
        const form = document.getElementById('rejectForm');
        form.action = `/leave-requests/${leaveRequestId}/reject`;
        document.getElementById('rejectModal').style.display = 'flex';
        document.getElementById('rejectionReason').focus();
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
        document.getElementById('rejectForm').reset();
    }

    function confirmDelete(leaveRequestId) {
        showDanger('Delete Leave Request', 'Are you sure you want to delete this leave request? This action cannot be undone.').then(function (confirmed) {
            if (!confirmed) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/leave-requests/${leaveRequestId}`;
            form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        });
    }

    document.getElementById('applyLeaveModal').addEventListener('click', function (e) {
        if (e.target === this) closeApplyLeaveModal();
    });

    document.getElementById('approveModal').addEventListener('click', function (e) {
        if (e.target === this) closeApproveModal();
    });

    document.getElementById('rejectModal').addEventListener('click', function (e) {
        if (e.target === this) closeRejectModal();
    });
</script>
@endpush
