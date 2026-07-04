@extends('tyro-dashboard::layouts.admin')

@section('title', 'Holiday Calendar')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Holiday Calendar</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Holiday Calendar</h1>
            <p class="page-description">Public and company holidays.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openAddHolidayModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Holiday
        </button>
    </div>
</div>

{{-- Summary Cards --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
    <div class="card">
        <div class="card-body">
            <p class="muted-text" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Total Holidays in {{ $totals['year'] }}</p>
            <p style="font-size: 1.875rem; font-weight: 700; color: var(--text); margin-top: 0.5rem;">{{ $totals['count'] }}</p>
        </div>
    </div>

    <div class="card" style="border-color: var(--success-200); background-color: white;">
        <div class="card-body">
            <p style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; color: var(--success-600);">Next Upcoming Holiday</p>
            @if($upcomingHoliday)
                <p style="font-size: 1.125rem; font-weight: 600; color: var(--success-900); margin-top: 0.5rem;">{{ $upcomingHoliday['name'] }}</p>
                <p style="font-size: 0.875rem; color: var(--success-700); margin-top: 0.25rem;">
                    {{ \Carbon\Carbon::parse($upcomingHoliday['date'])->format('M d, Y') }} •
                    @php
                        $days = $upcomingHoliday['days_until'];
                        if ($days < 0) {
                            $daysText = 'Passed';
                        } elseif ($days === 0) {
                            $daysText = 'Today';
                        } elseif ($days === 1) {
                            $daysText = 'In 1 day';
                        } else {
                            $daysText = "In {$days} days";
                        }
                    @endphp
                    {{ $daysText }}
                </p>
            @else
                <p style="font-size: 0.875rem; color: var(--success-700); margin-top: 0.5rem;">No upcoming holiday found.</p>
            @endif
        </div>
    </div>
</div>

{{-- View Toggle and Holiday List/Calendar --}}
<div class="card">
    <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <button type="button" class="btn holiday-view-btn active" onclick="switchView('list')" data-view="list">List View</button>
            <button type="button" class="btn holiday-view-btn" onclick="switchView('calendar')" data-view="calendar">Calendar View</button>
        </div>
        <p class="muted-text">{{ count($holidays) }} holidays total</p>
    </div>

    {{-- LIST VIEW --}}
    <div id="listView" class="holiday-view-container" style="display: block;">
        @if(count($holidays) > 0)
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Days Until</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($holidays as $holiday)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($holiday['date'])->format('M d, Y') }}</td>
                            <td><strong>{{ $holiday['name'] }}</strong></td>
                            <td>
                                <span class="badge {{ $holiday['type'] === 'Public' ? 'badge-info' : 'badge-warning' }}">{{ $holiday['type'] }}</span>
                            </td>
                            <td>
                                @php
                                    $days = $holiday['days_until'];
                                    if ($days < 0) {
                                        $daysText = 'Passed';
                                    } elseif ($days === 0) {
                                        $daysText = 'Today';
                                    } elseif ($days === 1) {
                                        $daysText = 'In 1 day';
                                    } else {
                                        $daysText = "In {$days} days";
                                    }
                                @endphp
                                {{ $daysText }}
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteHoliday({{ $holiday['id'] }})">Delete</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="padding: 2rem; text-align: center; color: var(--muted);">
                <p>No holidays configured yet. Click <strong>Add Holiday</strong> to create one.</p>
            </div>
        @endif
    </div>

    {{-- CALENDAR VIEW --}}
    <div id="calendarView" class="holiday-view-container" style="display: none; padding: 1.5rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <button type="button" class="btn calendar-mode-btn active" onclick="switchCalendarMode('monthly')" data-mode="monthly" style="font-size: 0.875rem; padding: 0.4rem 1rem;">Monthly</button>
                <button type="button" class="btn calendar-mode-btn" onclick="switchCalendarMode('yearly')" data-mode="yearly" style="font-size: 0.875rem; padding: 0.4rem 1rem;">Yearly</button>
            </div>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="previousPeriod()" id="prevBtn">← Prev</button>
                <h3 id="calendarPeriod" style="font-weight: 600; margin: 0; min-width: 120px; text-align: center;"></h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="nextPeriod()" id="nextBtn">Next →</button>
            </div>
        </div>

        <div id="calendarGrid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem;"></div>
    </div>
</div>

{{-- Add Holiday Modal --}}
<div id="addHolidayModal" style="display: none; position: fixed; inset: 0; z-index: 9000; background: rgb(250, 250, 250)); align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 1rem; width: 100%; max-width: 32rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);">
            <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Add New Holiday</h3>
        </div>

        <form id="addHolidayForm" method="POST" action="{{ route('holiday-calendar.store') }}" style="padding: 1.25rem 1.5rem 1.5rem;">
            @csrf
            <div class="form-group">
                <label for="holidayDate" class="form-label">Date</label>
                <input type="date" id="holidayDate" name="date" class="form-input" required>
                <span class="form-error" id="dateError" style="display: none;"></span>
            </div>

            <div class="form-group">
                <label for="holidayName" class="form-label">Name</label>
                <input type="text" id="holidayName" name="name" class="form-input" placeholder="National Mourning Day" required>
                <span class="form-error" id="nameError" style="display: none;"></span>
            </div>

            <div class="form-group">
                <label for="holidayType" class="form-label">Type</label>
                <select id="holidayType" name="type" class="form-input" required>
                    <option value="public">Public</option>
                    <option value="company">Company</option>
                </select>
                <span class="form-error" id="typeError" style="display: none;"></span>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                <button type="button" onclick="closeAddHolidayModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Holiday</button>
            </div>
        </form>
    </div>
</div>

<style>
    .holiday-view-btn {
        padding: 0.4rem 1rem;
        font-size: 0.875rem;
        border: 1px solid var(--border);
        background: var(--surface-1);
        color: var(--text);
        border-radius: 0.375rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .holiday-view-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .holiday-view-btn:hover:not(.active) {
        background: var(--surface-2);
    }

    .calendar-mode-btn {
        padding: 0.4rem 1rem;
        font-size: 0.875rem;
        border: 1px solid var(--border);
        background: var(--surface-1);
        color: var(--text);
        border-radius: 0.375rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .calendar-mode-btn.active {
        background: var(--secondary);
        color: white;
        border-color: var(--secondary);
    }

    .calendar-mode-btn:hover:not(.active) {
        background: var(--surface-2);
    }

    .calendar-weekend {
        background-color: rgba(239, 68, 68, 0.05) !important;
        border-color: rgba(239, 68, 68, 0.2) !important;
    }

    .calendar-cell-large {
        min-height: 100px;
    }

    .calendar-cell-small {
        min-height: 60px;
    }
</style>

@endsection

@push('scripts')
<script>
    let currentMonth = {{ $calendarMonth['month'] }};
    let currentYear = {{ $calendarMonth['year'] }};
    let calendarMode = 'monthly';
    const holidays = {!! json_encode($holidays) !!};
    const monthLabels = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const weekdayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri (Weekend)', 'Sat'];

    function switchView(view) {
        document.getElementById('listView').style.display = view === 'list' ? 'block' : 'none';
        document.getElementById('calendarView').style.display = view === 'calendar' ? 'block' : 'none';

        document.querySelectorAll('.holiday-view-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`.holiday-view-btn[data-view="${view}"]`).classList.add('active');

        if (view === 'calendar') {
            renderCalendar();
        }
    }

    function switchCalendarMode(mode) {
        calendarMode = mode;
        document.querySelectorAll('.calendar-mode-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`.calendar-mode-btn[data-mode="${mode}"]`).classList.add('active');
        renderCalendar();
    }

    function renderCalendar() {
        if (calendarMode === 'monthly') {
            renderMonthly();
        } else {
            renderYearly();
        }
    }

    function renderMonthly() {
        const grid = document.getElementById('calendarGrid');
        grid.innerHTML = '';
        grid.style.gridTemplateColumns = 'repeat(7, 1fr)';

        // Render weekday labels
        weekdayLabels.forEach((day, index) => {
            const dayEl = document.createElement('div');
            const isWeekend = index === 5; // Friday
            dayEl.style.cssText = `
                padding: 0.75rem 0.5rem;
                text-align: center;
                font-weight: 700;
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: ${isWeekend ? 'var(--danger)' : 'var(--muted)'};
                background: ${isWeekend ? 'rgba(239, 68, 68, 0.1)' : 'var(--surface-1)'};
                border-radius: 0.375rem;
            `;
            dayEl.textContent = day;
            grid.appendChild(dayEl);
        });

        const firstDay = new Date(currentYear, currentMonth - 1, 1);
        const firstDayWeek = firstDay.getDay();
        const daysInCurrentMonth = new Date(currentYear, currentMonth, 0).getDate();
        const daysInPreviousMonth = new Date(currentYear, currentMonth - 1, 0).getDate();

        // Previous month days
        for (let i = firstDayWeek - 1; i >= 0; i--) {
            const day = daysInPreviousMonth - i;
            addCalendarCell(day, false, null);
        }

        // Current month days
        for (let day = 1; day <= daysInCurrentMonth; day++) {
            const dateStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const holiday = holidays.find(h => h.date === dateStr);
            const dayOfWeek = new Date(currentYear, currentMonth - 1, day).getDay();
            addCalendarCell(day, true, holiday, dayOfWeek === 5);
        }

        // Next month days
        let nextDay = 1;
        while (grid.children.length % 7 !== 0) {
            addCalendarCell(nextDay, false, null);
            nextDay++;
        }

        document.getElementById('calendarPeriod').textContent = `${monthLabels[currentMonth - 1]} ${currentYear}`;
    }

    function renderYearly() {
        const grid = document.getElementById('calendarGrid');
        grid.innerHTML = '';
        grid.style.gridTemplateColumns = 'repeat(3, 1fr)';

        for (let month = 1; month <= 12; month++) {
            const monthContainer = document.createElement('div');
            monthContainer.style.cssText = `
                padding: 1rem;
                border: 1px solid var(--border);
                border-radius: 0.5rem;
                background: var(--surface-1);
            `;

            const monthTitle = document.createElement('h4');
            monthTitle.style.cssText = `
                margin: 0 0 1rem 0;
                font-size: 0.875rem;
                font-weight: 600;
                text-align: center;
                color: var(--text);
            `;
            monthTitle.textContent = monthLabels[month - 1];
            monthContainer.appendChild(monthTitle);

            const miniGrid = document.createElement('div');
            miniGrid.style.cssText = 'display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.25rem;';

            // Mini weekday labels
            ['S', 'M', 'T', 'W', 'T', 'F', 'S'].forEach((day, index) => {
                const dayEl = document.createElement('div');
                const isWeekend = index === 5; // Friday
                dayEl.style.cssText = `
                    padding: 0.25rem;
                    text-align: center;
                    font-weight: 700;
                    font-size: 0.625rem;
                    color: ${isWeekend ? 'var(--danger)' : 'var(--muted)'};
                `;
                dayEl.textContent = day;
                miniGrid.appendChild(dayEl);
            });

            const firstDay = new Date(currentYear, month - 1, 1);
            const firstDayWeek = firstDay.getDay();
            const daysInMonth = new Date(currentYear, month, 0).getDate();
            const daysInPreviousMonth = new Date(currentYear, month - 1, 0).getDate();

            // Previous month days
            for (let i = firstDayWeek - 1; i >= 0; i--) {
                const day = daysInPreviousMonth - i;
                addMiniCalendarCell(miniGrid, day, false, null);
            }

            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${currentYear}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const holiday = holidays.find(h => h.date === dateStr);
                const dayOfWeek = new Date(currentYear, month - 1, day).getDay();
                addMiniCalendarCell(miniGrid, day, true, holiday, dayOfWeek === 5);
            }

            // Next month days
            let nextDay = 1;
            while (miniGrid.children.length % 7 !== 0) {
                addMiniCalendarCell(miniGrid, nextDay, false, null);
                nextDay++;
            }

            monthContainer.appendChild(miniGrid);
            grid.appendChild(monthContainer);
        }

        document.getElementById('calendarPeriod').textContent = currentYear;
    }

    function addCalendarCell(day, inCurrentMonth, holiday, isWeekend = false) {
        const grid = document.getElementById('calendarGrid');
        const cell = document.createElement('div');

        let bgColor = inCurrentMonth ? 'var(--surface-1)' : 'var(--surface-2)';
        let borderColor = inCurrentMonth ? 'var(--border)' : 'var(--surface-3)';
        let textColor = inCurrentMonth ? 'var(--text)' : 'var(--muted)';

        if (isWeekend && inCurrentMonth) {
            bgColor = 'rgba(239, 68, 68, 0.05)';
            borderColor = 'rgba(239, 68, 68, 0.2)';
        }

        cell.style.cssText = `
            min-height: 100px;
            padding: 0.75rem;
            border: 1px solid ${borderColor};
            background: ${bgColor};
            border-radius: 0.5rem;
            font-size: 0.875rem;
            display: flex;
            flex-direction: column;
            transition: all 0.2s;
            cursor: pointer;
        `;

        cell.addEventListener('mouseenter', () => {
            if (inCurrentMonth) {
                cell.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                cell.style.transform = 'translateY(-2px)';
            }
        });

        cell.addEventListener('mouseleave', () => {
            cell.style.boxShadow = 'none';
            cell.style.transform = 'none';
        });

        const dayEl = document.createElement('p');
        dayEl.style.cssText = `margin: 0; font-weight: 700; color: ${textColor}; font-size: 1rem;`;
        dayEl.textContent = day;
        cell.appendChild(dayEl);

        if (holiday) {
            const holidayEl = document.createElement('div');
            holidayEl.style.cssText = `
                margin-top: auto;
                padding: 0.5rem;
                background: var(--success-50);
                border-radius: 0.375rem;
                font-size: 0.75rem;
                font-weight: 600;
                color: var(--success-700);
                overflow: hidden;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                border-left: 3px solid var(--success);
                padding-left: 0.375rem;
            `;
            holidayEl.textContent = holiday.name;
            cell.appendChild(holidayEl);
        }

        grid.appendChild(cell);
    }

    function addMiniCalendarCell(grid, day, inCurrentMonth, holiday, isWeekend = false) {
        const cell = document.createElement('div');

        let bgColor = inCurrentMonth ? (isWeekend ? 'rgba(239, 68, 68, 0.1)' : 'var(--surface-2)') : 'transparent';
        let borderColor = holiday ? 'var(--success)' : 'var(--surface-3)';
        let textColor = inCurrentMonth ? 'var(--text)' : 'var(--muted)';
        let fontWeight = holiday ? '700' : '500';

        if (isWeekend && inCurrentMonth) {
            textColor = 'var(--danger)';
        }

        cell.style.cssText = `
            padding: 0.25rem;
            text-align: center;
            font-size: 0.625rem;
            font-weight: ${fontWeight};
            color: ${textColor};
            background: ${bgColor};
            border: ${holiday ? '1px solid ' + borderColor : 'none'};
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.15s;
        `;

        cell.addEventListener('mouseenter', () => {
            if (holiday) {
                cell.style.background = 'var(--success-100)';
            }
        });

        cell.addEventListener('mouseleave', () => {
            cell.style.background = bgColor;
        });

        cell.textContent = day;
        grid.appendChild(cell);
    }

    function previousPeriod() {
        if (calendarMode === 'monthly') {
            if (currentMonth === 1) {
                currentMonth = 12;
                currentYear--;
            } else {
                currentMonth--;
            }
        } else {
            currentYear--;
        }
        renderCalendar();
    }

    function nextPeriod() {
        if (calendarMode === 'monthly') {
            if (currentMonth === 12) {
                currentMonth = 1;
                currentYear++;
            } else {
                currentMonth++;
            }
        } else {
            currentYear++;
        }
        renderCalendar();
    }

    function openAddHolidayModal() {
        document.getElementById('addHolidayModal').style.display = 'flex';
        document.getElementById('holidayDate').focus();
    }

    function closeAddHolidayModal() {
        document.getElementById('addHolidayModal').style.display = 'none';
        document.getElementById('addHolidayForm').reset();
    }

    function confirmDeleteHoliday(id) {
        showDanger('Delete Holiday', 'Are you sure you want to delete this holiday?').then(function (confirmed) {
            if (!confirmed) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/holiday-calendar/' + id;
            form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        });
    }

    document.getElementById('addHolidayModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeAddHolidayModal();
        }
    });
</script>
@endpush
