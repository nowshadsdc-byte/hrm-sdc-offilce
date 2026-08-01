@extends('tyro-dashboard::layouts.user')

@section('title', 'My Leave Requests')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>My Leave Requests</span>
@endsection

@section('content')
@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag())
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">My Leave Requests</h1>
            <p class="page-description">Submit and track your leave requests here.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openApplyLeaveModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Apply Leave
        </button>
    </div>
</div>

@if (session('success'))
<div style="margin-bottom: 1rem; padding: 0.9rem 1rem; border-radius: 0.5rem; background: rgba(16, 185, 129, 0.12); color: #047857; border: 1px solid rgba(16, 185, 129, 0.25);">
    {{ session('success') }}
</div>
@endif

@if ($errors->any())
<div style="margin-bottom: 1rem; padding: 0.9rem 1rem; border-radius: 0.5rem; background: rgba(239, 68, 68, 0.12); color: #b91c1c; border: 1px solid rgba(239, 68, 68, 0.25);">
    <ul style="margin: 0; padding-left: 1.2rem;">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

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

    <div class="card" style="border-left: 4px solid #2563eb;">
        <div class="card-body">
            <p class="muted-text" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Annual Leave Days</p>
            <p style="font-size: 2rem; font-weight: 700; color: #2563eb; margin-top: 0.5rem;">{{ $leaveBalance['total'] }}</p>
            <p style="margin: 0.35rem 0 0; font-size: 0.9rem; color: #374151;">Left Leave: {{ $leaveBalance['remaining'] }}</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <a href="{{ route('dashboard.myleaverequests', ['tab' => 'all']) }}" class="btn leave-req-tab-btn {{ $tab === 'all' ? 'active' : '' }}">All Requests</a>
            <a href="{{ route('dashboard.myleaverequests', ['tab' => 'pending']) }}" class="btn leave-req-tab-btn {{ $tab === 'pending' ? 'active' : '' }}">Pending</a>
            <a href="{{ route('dashboard.myleaverequests', ['tab' => 'approved']) }}" class="btn leave-req-tab-btn {{ $tab === 'approved' ? 'active' : '' }}">Approved</a>
            <a href="{{ route('dashboard.myleaverequests', ['tab' => 'rejected']) }}" class="btn leave-req-tab-btn {{ $tab === 'rejected' ? 'active' : '' }}">Rejected</a>
        </div>
        <p class="muted-text">{{ count($leaveRequests) }} requests</p>
    </div>

    <div style="padding: 1.5rem;">
        @if($leaveRequests->isNotEmpty())
            <div style="display: grid; gap: 1rem;">
                @foreach($leaveRequests as $request)
                    <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; background: white; display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: start; color: #000;">
                        <div>
                            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                <div style="display: inline-block; padding: 0.375rem 0.75rem; background: #dbeafe; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #1e40af;">
                                    {{ $request->leave_type }}
                                </div>
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
                                    {{ ucfirst($request->status) }}
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding: 0.75rem; background: #f3f4f6; border-radius: 0.5rem;">
                                <p style="font-size: 0.875rem; font-weight: 600; margin: 0; color: #000;">{{ $request->start_date->format('M d, Y') }}</p>
                                <div style="height: 2px; background: #e5e7eb; border-radius: 1px;"></div>
                                <p style="font-size: 0.875rem; font-weight: 600; margin: 0; color: #000;">{{ $request->end_date->format('M d, Y') }}</p>
                            </div>

                            @if($request->reason)
                                <div style="font-size: 0.9rem; color: #374151;">
                                    {!! $request->reason !!}
                                </div>
                            @endif
                        </div>

                        <div style="text-align: right; color: #6b7280; font-size: 0.8rem; min-width: 120px; display: flex; flex-direction: column; gap: 0.5rem;">
                            <div>{{ $request->created_at->diffForHumans() }}</div>
                            <div>{{ $request->days_count }} day{{ $request->days_count > 1 ? 's' : '' }}</div>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="openLeaveDetailsModal({{ $request->id }})">
                                View
                            </button>
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

<div id="applyLeaveModal" style="display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,0.45); align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 1rem; width: 100%; max-width: 40rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); color: #000;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);">
            <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Apply for Leave</h3>
        </div>

        <form id="selfLeaveForm" method="POST" action="{{ route('dashboard.myleaverequests.store') }}" style="padding: 1.25rem 1.5rem 1.5rem;">
            @csrf

            @if ($employee)
                <div class="form-group">
                    <label for="employeeName" class="form-label">Employee</label>
                    <input id="employeeName" type="text" class="form-input" value="{{ $employee->name }}" readonly>
                </div>
            @else
                <div style="margin-bottom: 1rem; padding: 0.875rem 1rem; border-radius: 0.5rem; background: rgba(245, 158, 11, 0.12); color: #92400e; border: 1px solid rgba(245, 158, 11, 0.25);">
                    Your employee profile is not linked yet. Please contact HR before submitting a leave request.
                </div>
            @endif

            <div class="form-group">
                <label for="leaveType" class="form-label">Leave Type</label>
                <select id="leaveType" name="leave_type" class="form-input" required {{ $employee ? '' : 'disabled' }}>
                    <option value="">Select leave type</option>
                    @foreach($leaveTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="startDate" class="form-label">Start Date</label>
                    <input type="date" id="startDate" name="start_date" class="form-input" required {{ $employee ? '' : 'disabled' }}>
                </div>

                <div class="form-group">
                    <label for="endDate" class="form-label">End Date</label>
                    <input type="date" id="endDate" name="end_date" class="form-input" required {{ $employee ? '' : 'disabled' }}>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Reason</label>
                <div class="rich-text-editor">
                    <div class="rich-text-toolbar">
                        <button type="button" class="rich-text-btn" data-rich-command="bold"><strong>B</strong></button>
                        <button type="button" class="rich-text-btn" data-rich-command="italic"><em>I</em></button>
                        <button type="button" class="rich-text-btn" data-rich-command="underline"><u>U</u></button>
                        <button type="button" class="rich-text-btn" data-rich-command="insertUnorderedList">• List</button>
                    </div>
                    <div id="selfLeaveReasonEditor" contenteditable="true" class="rich-text-content" style="min-height: 8rem;" {{ $employee ? '' : 'contenteditable="false"' }}></div>
                    <textarea id="selfLeaveReason" name="reason" hidden></textarea>
                </div>
                <p class="muted-text" style="margin-top: 0.5rem;">Use the editor to describe your reason for the leave request.</p>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                <button type="button" onclick="closeApplyLeaveModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary" {{ $employee ? '' : 'disabled' }}>Submit Request</button>
            </div>
        </form>
    </div>
</div>
<div id="leaveDetailsModal" style="display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,0.45); align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 1rem; width: 100%; max-width: 38rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); color: #000;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
            <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Leave Request Details</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeLeaveDetailsModal()">Close</button>
        </div>
        <div id="leaveDetailsContent" style="padding: 1.25rem 1.5rem;"></div>
    </div>
</div>
@endsection

@push('styles')
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

    .rich-text-editor {
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        overflow: hidden;
        background: var(--surface-1);
    }

    .rich-text-toolbar {
        display: flex;
        gap: 0.35rem;
        padding: 0.5rem;
        background: #f3f4f6;
        border-bottom: 1px solid var(--border);
    }

    .rich-text-btn {
        border: 1px solid var(--border);
        background: white;
        border-radius: 0.35rem;
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
        cursor: pointer;
    }

    .rich-text-content {
        min-height: 8rem;
        padding: 0.75rem 1rem;
        outline: none;
        background: white;
    }
</style>
@endpush

@push('scripts')
<script>
    const leaveRequestDetails = @json($leaveRequestDetails);

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('selfLeaveForm');
        const editor = document.getElementById('selfLeaveReasonEditor');
        const hiddenReason = document.getElementById('selfLeaveReason');

        if (form && editor && hiddenReason) {
            document.querySelectorAll('[data-rich-command]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const command = this.getAttribute('data-rich-command');
                    document.execCommand(command, false, null);
                    editor.focus();
                });
            });

            form.addEventListener('submit', function () {
                hiddenReason.value = editor.innerHTML;
            });
        }
    });

    function openApplyLeaveModal() {
        document.getElementById('applyLeaveModal').style.display = 'flex';
        document.getElementById('selfLeaveReasonEditor').focus();
    }

    function closeApplyLeaveModal() {
        document.getElementById('applyLeaveModal').style.display = 'none';
        document.getElementById('selfLeaveForm').reset();
        document.getElementById('selfLeaveReasonEditor').innerHTML = '';
        document.getElementById('selfLeaveReason').value = '';
    }

    function openLeaveDetailsModal(requestId) {
        const request = leaveRequestDetails.find(function (item) {
            return item.id === requestId;
        });

        if (!request) {
            return;
        }

        const content = document.getElementById('leaveDetailsContent');
        content.innerHTML = `
            <div style="display: grid; gap: 0.85rem;">
                <div>
                    <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #6b7280; margin: 0 0 0.25rem;">Leave Type</p>
                    <p style="margin: 0; font-size: 1rem; font-weight: 600; color: #111827;">${request.leave_type}</p>
                </div>
                <div>
                    <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #6b7280; margin: 0 0 0.25rem;">Status</p>
                    <p style="margin: 0; font-size: 1rem; font-weight: 600; color: #111827; text-transform: capitalize;">${request.status}</p>
                </div>
                <div>
                    <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #6b7280; margin: 0 0 0.25rem;">Duration</p>
                    <p style="margin: 0; font-size: 1rem; font-weight: 600; color: #111827;">${request.days_count} day${request.days_count > 1 ? 's' : ''}</p>
                </div>
                <div>
                    <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #6b7280; margin: 0 0 0.25rem;">Dates</p>
                    <p style="margin: 0; font-size: 1rem; font-weight: 600; color: #111827;">${request.start_date} &ndash; ${request.end_date}</p>
                </div>
                <div>
                    <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #6b7280; margin: 0 0 0.25rem;">Reason</p>
                    <div style="margin: 0; font-size: 0.95rem; color: #374151;">${request.reason ? request.reason : '<span style=\"color:#9ca3af\">No reason provided.</span>'}</div>
                </div>
                <div>
                    <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #6b7280; margin: 0 0 0.25rem;">Applied On</p>
                    <p style="margin: 0; font-size: 1rem; font-weight: 600; color: #111827;">${request.created_at}</p>
                </div>
            </div>
        `;

        document.getElementById('leaveDetailsModal').style.display = 'flex';
    }

    function closeLeaveDetailsModal() {
        document.getElementById('leaveDetailsModal').style.display = 'none';
    }

    document.getElementById('applyLeaveModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeApplyLeaveModal();
        }
    });

    document.getElementById('leaveDetailsModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeLeaveDetailsModal();
        }
    });
</script>
@endpush
