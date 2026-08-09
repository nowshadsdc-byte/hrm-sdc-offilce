@props([
    'records' => [],
    'summary' => [
        'date' => '',
        'present' => 0,
        'late' => 0,
        'absent' => 0,
        'on_leave' => 0,
    ],
    'pagination' => [
        'current_page' => 1,
        'last_page' => 1,
        'from' => 0,
        'to' => 0,
        'total' => 0,
    ],
])

@php
    $statusClasses = [
        'Present' => 'bg-emerald-100 text-emerald-700',
        'Late' => 'bg-amber-100 text-amber-700',
        'Absent' => 'bg-rose-100 text-rose-700',
        'On Leave' => 'bg-sky-100 text-sky-700',
    ];
@endphp

<section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
    <header class="flex flex-wrap items-start justify-between gap-2 border-b border-slate-200 px-4 py-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z" />
                    </svg>
                </span>
                <h2 class="text-lg font-semibold text-slate-900">Attendance Records</h2>
            </div>
            <p class="mt-1 text-sm text-slate-500">
                {{ $summary['date'] }} · {{ $summary['present'] }} present · {{ $summary['late'] }} late · {{ $summary['absent'] }} absent · {{ $summary['on_leave'] }} on leave
            </p>
        </div>
    </header>

    <p class="border-b border-slate-100 px-4 py-2 text-xs text-slate-500 lg:hidden">
        Swipe horizontally to view all attendance columns.
    </p>

    <div class="max-h-140 overflow-auto">
        <table class="min-w-315 w-full text-sm lg:min-w-full">
            <thead class="sticky top-0 z-10 bg-white">
                <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3 whitespace-nowrap">Employee</th>
                    <th class="px-4 py-3 whitespace-nowrap">
                        <button type="button" class="inline-flex items-center gap-1">Check In
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 15l5 5 5-5M7 9l5-5 5 5"/>
                            </svg>
                        </button>
                    </th>
                    <th class="px-4 py-3 whitespace-nowrap">Lunch Start</th>
                    <th class="px-4 py-3 whitespace-nowrap">Lunch End</th>
                    <th class="px-4 py-3 whitespace-nowrap">Lunch</th>
                    <th class="px-4 py-3 whitespace-nowrap">
                        <button type="button" class="inline-flex items-center gap-1">Check Out
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 15l5 5 5-5M7 9l5-5 5 5"/>
                            </svg>
                        </button>
                    </th>
                    <th class="px-4 py-3 whitespace-nowrap">
                        <button type="button" class="inline-flex items-center gap-1">Hours
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 15l5 5 5-5M7 9l5-5 5 5"/>
                            </svg>
                        </button>
                    </th>
                    <th class="px-4 py-3 whitespace-nowrap">Overtime</th>
                    <th class="px-4 py-3 whitespace-nowrap">Late</th>
                    <th class="px-4 py-3 whitespace-nowrap">Status</th>
                    <th class="px-4 py-3">Remarks</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-4 py-4 align-top">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full text-sm font-semibold {{ $record['avatar_class'] }}">
                                    {{ $record['avatar_initials'] }}
                                </span>
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $record['name'] }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $record['title'] }} · {{ $record['employee_id'] }}</p>
                                    <span class="mt-2 inline-flex rounded-full border border-slate-300 bg-white px-2.5 py-0.5 text-[11px] font-medium text-slate-600">
                                        {{ $record['department'] }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-600">{{ $record['check_in'] }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-600">{{ $record['lunch_start'] }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-600">{{ $record['lunch_end'] }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-600">{{ $record['lunch'] }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-600">{{ $record['check_out'] }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-700 font-medium">{{ $record['hours'] }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-600">{{ $record['overtime'] }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-slate-500">{{ $record['late'] }}</td>
                        <td class="px-4 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$record['status']] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $record['status'] }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-slate-500">
                            <p class="max-w-64 whitespace-normal wrap-break-word">{{ $record['remarks'] }}</p>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-sm text-slate-500">No attendance records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3">
        <p class="text-sm text-slate-500">
            Page {{ $pagination['current_page'] }} of {{ $pagination['last_page'] }} · {{ $pagination['from'] }}-{{ $pagination['to'] }} of {{ $pagination['total'] }}
        </p>

        <div class="inline-flex items-center gap-1.5 text-sm">
            <button type="button" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-slate-600 hover:bg-slate-50">Prev</button>
            @for ($page = 1; $page <= $pagination['last_page']; $page++)
                <button
                    type="button"
                    class="rounded-md px-3 py-1.5 {{ $page === $pagination['current_page'] ? 'bg-emerald-600 text-white' : 'border border-slate-300 bg-white text-slate-600 hover:bg-slate-50' }}"
                >
                    {{ $page }}
                </button>
            @endfor
            <button type="button" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-slate-600 hover:bg-slate-50">Next</button>
        </div>
    </footer>
</section>
