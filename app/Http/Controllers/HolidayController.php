<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        $holidayRows = Holiday::query()->orderBy('date', 'asc')->get();

        $holidays = $holidayRows
            ->map(fn (Holiday $holiday): array => [
                'id' => $holiday->id,
                'date' => $holiday->date?->toDateString(),
                'name' => $holiday->name,
                'type' => ucfirst($holiday->type),
                'days_until' => $holiday->date ? $today->diffInDays($holiday->date, false) : null,
            ])
            ->values();

        $upcoming = $holidayRows
            ->first(fn (Holiday $holiday): bool => (bool) $holiday->date && $holiday->date->greaterThanOrEqualTo($today));

        $currentMonth = (int) $request->integer('month', now()->month);
        $currentYear = (int) $request->integer('year', now()->year);

        return view('dashboard.holiday-calendar', [
            'holidays' => $holidays,
            'upcomingHoliday' => $upcoming ? [
                'id' => $upcoming->id,
                'date' => $upcoming->date?->toDateString(),
                'name' => $upcoming->name,
                'type' => ucfirst($upcoming->type),
                'days_until' => $today->diffInDays($upcoming->date, false),
            ] : null,
            'totals' => [
                'year' => now()->year,
                'count' => $holidayRows->filter(fn (Holiday $holiday): bool => $holiday->date?->year === now()->year)->count(),
            ],
            'calendarMonth' => [
                'month' => $currentMonth,
                'year' => $currentYear,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:public,company'],
        ]);

        $holidayDate = Carbon::parse($validated['date'])->startOfDay();

        Holiday::query()->create([
            'date' => $holidayDate,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'days_until' => Carbon::today()->diffInDays($holidayDate, false),
        ]);

        return back()->with('success', 'Holiday added successfully.');
    }

    public function destroy(Holiday $holiday)
    {
        Holiday::destroy($holiday->id);

        return back()->with('success', 'Holiday deleted successfully.');
    }
}
