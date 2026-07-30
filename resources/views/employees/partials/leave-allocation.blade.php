<div class="card" style="margin-top: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--border);">
    <div class="card-header" style="display: flex; align-items: center; gap: 0.65rem;">
        <span style="display: inline-flex; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; border-radius: 0.65rem; background: #f0fdf4; color: #15803d; flex-shrink: 0;">
            <svg viewBox="0 0 24 24" style="width: 1.15rem; height: 1.15rem;" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 13c2 0 2-2 4-2s2 2 4 2 2-2 4-2" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 18c2 0 2-2 4-2s2 2 4 2 2-2 4-2 2 2 4 2" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8c1.5-2.5 5-3.5 7-1 2-2.5 5.5-1.5 7 1" />
            </svg>
        </span>
        <div>
            <h3 class="card-title" style="margin: 0;">Leave Allocation</h3>
            <p style="margin: 0.15rem 0 0; font-size: 0.8rem; color: #6b7280;">Annual leave days this employee is granted. Leave blank to use the organization default.</p>
        </div>
    </div>
    <div class="card-body">
        <div class="form-group" style="margin: 0; max-width: 16rem;">
            <label for="annual_leave_days" class="form-label">Annual Leave Days (override)</label>
            @if ($isAdmin ?? false)
                <input
                    type="number"
                    id="annual_leave_days"
                    name="annual_leave_days"
                    min="0"
                    max="365"
                    class="form-input @error('annual_leave_days') is-invalid @enderror"
                    value="{{ old('annual_leave_days', $selectedAnnualLeaveDays ?? '') }}"
                    placeholder="Default: {{ $defaultAnnualLeaveDays ?? 20 }}"
                >
                @error('annual_leave_days')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            @else
                <input type="number" id="annual_leave_days" class="form-input" value="{{ $selectedAnnualLeaveDays ?? '' }}" placeholder="Default: {{ $defaultAnnualLeaveDays ?? 20 }}" disabled>
                <p style="margin-top: 0.5rem; font-size: 0.78rem; color: #9ca3af;">Only admins can change leave allocation.</p>
            @endif
        </div>
    </div>
</div>
