@php
    $defaultShift = $shifts->firstWhere('is_default', true);
    $selectedShiftId = old('shift_id', $selectedShiftId ?? $defaultShift?->id);
@endphp

<div class="card" style="margin-top: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--border);">
    <div class="card-header" style="display: flex; align-items: center; gap: 0.65rem;">
        <span style="display: inline-flex; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; border-radius: 0.65rem; background: #eef2ff; color: #4338ca; flex-shrink: 0;">
            <svg viewBox="0 0 24 24" style="width: 1.15rem; height: 1.15rem;" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3" />
            </svg>
        </span>
        <div>
            <h3 class="card-title" style="margin: 0;">Shifts & Schedule</h3>
            <p style="margin: 0.15rem 0 0; font-size: 0.8rem; color: #6b7280;">Sets the working hours used to flag late check-ins and early leaves on the attendance sheet.</p>
        </div>
    </div>
    <div class="card-body">
        <div class="form-group" style="margin: 0;">
            <label for="shift_id" class="form-label">Assigned Shift</label>
            <select id="shift_id" name="shift_id" class="form-select @error('shift_id') is-invalid @enderror" data-shift-select style="max-width: 28rem;">
                @forelse ($shifts as $shift)
                    <option
                        value="{{ $shift->id }}"
                        data-range="{{ $shift->formattedRange() }}"
                        {{ (string) $selectedShiftId === (string) $shift->id ? 'selected' : '' }}
                    >
                        {{ $shift->name }} &middot; {{ $shift->formattedRange() }}{{ $shift->is_default ? ' (Default)' : '' }}
                    </option>
                @empty
                    <option value="" disabled selected>No shifts configured yet</option>
                @endforelse
            </select>
            @error('shift_id')
                <span class="form-error">{{ $message }}</span>
            @enderror

            <div style="display: inline-flex; align-items: center; gap: 0.4rem; margin-top: 0.65rem; padding: 0.35rem 0.75rem; border-radius: 999px; background: #f0fdf4; border: 1px solid #bbf7d0; font-size: 0.8rem; color: #166534; font-weight: 600;">
                <svg viewBox="0 0 24 24" style="width: 0.9rem; height: 0.9rem;" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span data-shift-range-preview>{{ optional($shifts->firstWhere('id', (int) $selectedShiftId))->formattedRange() ?? ($defaultShift?->formattedRange() ?? '10:30 AM - 6:00 PM') }}</span>
            </div>

            <p style="margin-top: 0.6rem; font-size: 0.78rem; color: #9ca3af;">
                New employees default to <strong>{{ $defaultShift?->name ?? 'Default Shift' }}</strong> ({{ $defaultShift?->formattedRange() ?? '10:30 AM - 6:00 PM' }}). Change the dropdown to assign a different shift for this employee.
            </p>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('change', function (event) {
                var select = event.target.closest('[data-shift-select]');
                if (!select) {
                    return;
                }

                var preview = select.closest('.card-body').querySelector('[data-shift-range-preview]');
                var option = select.options[select.selectedIndex];

                if (preview && option) {
                    preview.textContent = option.dataset.range || '';
                }
            });
        </script>
    @endpush
@endonce
