import { Head, router, useForm } from '@inertiajs/react';
import {
    destroy,
    store,
} from '@/actions/App/Http/Controllers/HolidayController';
import { useMemo, useState } from 'react';

type Holiday = {
    id: number;
    date: string;
    name: string;
    type: string;
    days_until: number;
};

type HolidayCalendarPageProps = {
    holidays: Holiday[];
    upcomingHoliday: Holiday | null;
    totals: {
        year: number;
        count: number;
    };
    calendarMonth: {
        month: number;
        year: number;
    };
};

type CalendarCell = {
    isoDate: string;
    dayNumber: number;
    inCurrentMonth: boolean;
};

const weekdayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const monthLabels = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString(undefined, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatDaysUntil(days: number): string {
    if (days < 0) {
        return 'Passed';
    }

    if (days === 0) {
        return 'Today';
    }

    if (days === 1) {
        return 'In 1 day';
    }

    return `In ${days} days`;
}

function getCalendarCells(year: number, month: number): CalendarCell[] {
    const firstDay = new Date(year, month - 1, 1);
    const firstDayWeek = firstDay.getDay();
    const daysInCurrentMonth = new Date(year, month, 0).getDate();
    const daysInPreviousMonth = new Date(year, month - 1, 0).getDate();

    const cells: CalendarCell[] = [];

    for (let i = firstDayWeek - 1; i >= 0; i -= 1) {
        const day = daysInPreviousMonth - i;
        const date = new Date(year, month - 2, day);

        cells.push({
            isoDate: date.toISOString().slice(0, 10),
            dayNumber: day,
            inCurrentMonth: false,
        });
    }

    for (let day = 1; day <= daysInCurrentMonth; day += 1) {
        const date = new Date(year, month - 1, day);

        cells.push({
            isoDate: date.toISOString().slice(0, 10),
            dayNumber: day,
            inCurrentMonth: true,
        });
    }

    while (cells.length % 7 !== 0) {
        const day = cells.length - (firstDayWeek + daysInCurrentMonth) + 1;
        const date = new Date(year, month, day);

        cells.push({
            isoDate: date.toISOString().slice(0, 10),
            dayNumber: day,
            inCurrentMonth: false,
        });
    }

    return cells;
}

export default function HolidayCalendarIndex(props: HolidayCalendarPageProps) {
    const [isAddOpen, setIsAddOpen] = useState(false);
    const [viewMode, setViewMode] = useState<'list' | 'calendar'>('list');
    const [month, setMonth] = useState(props.calendarMonth.month);
    const [year, setYear] = useState(props.calendarMonth.year);

    const { data, setData, post, processing, errors, reset } = useForm({
        date: '',
        name: '',
        type: 'public',
    });

    const holidayMap = useMemo(() => {
        const map = new Map<string, Holiday>();

        props.holidays.forEach((holiday) => {
            map.set(holiday.date, holiday);
        });

        return map;
    }, [props.holidays]);

    const calendarCells = useMemo(
        () => getCalendarCells(year, month),
        [year, month],
    );

    const handleAddHoliday = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setIsAddOpen(false);
            },
        });
    };

    const handleDeleteHoliday = (holidayId: number) => {
        router.delete(destroy.url(holidayId), {
            preserveScroll: true,
        });
    };

    const goToPreviousMonth = () => {
        if (month === 1) {
            setMonth(12);
            setYear((current) => current - 1);
            return;
        }

        setMonth((current) => current - 1);
    };

    const goToNextMonth = () => {
        if (month === 12) {
            setMonth(1);
            setYear((current) => current + 1);
            return;
        }

        setMonth((current) => current + 1);
    };

    return (
        <>
            <Head title="Holiday Calendar" />

            <div className="space-y-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">
                            Holiday Calendar
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Public and company holidays.
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={() => setIsAddOpen(true)}
                        className="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    >
                        + Add Holiday
                    </button>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p className="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Total Holidays in {props.totals.year}
                        </p>
                        <p className="mt-2 text-2xl font-bold text-slate-900">
                            {props.totals.count}
                        </p>
                    </div>

                    <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                        <p className="text-xs font-medium uppercase tracking-wide text-emerald-500">
                            Next Upcoming Holiday
                        </p>
                        {props.upcomingHoliday ? (
                            <>
                                <p className="mt-2 text-lg font-semibold text-emerald-900">
                                    {props.upcomingHoliday.name}
                                </p>
                                <p className="mt-1 text-sm text-emerald-700">
                                    {formatDate(props.upcomingHoliday.date)} •{' '}
                                    {formatDaysUntil(props.upcomingHoliday.days_until)}
                                </p>
                            </>
                        ) : (
                            <p className="mt-2 text-sm text-emerald-700">
                                No upcoming holiday found.
                            </p>
                        )}
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                        <div className="inline-flex rounded-md border border-slate-200 bg-slate-50 p-1">
                            <button
                                type="button"
                                onClick={() => setViewMode('list')}
                                className={`rounded px-3 py-1.5 text-xs font-semibold ${
                                    viewMode === 'list'
                                        ? 'bg-white text-slate-900 shadow-sm'
                                        : 'text-slate-600'
                                }`}
                            >
                                List View
                            </button>
                            <button
                                type="button"
                                onClick={() => setViewMode('calendar')}
                                className={`rounded px-3 py-1.5 text-xs font-semibold ${
                                    viewMode === 'calendar'
                                        ? 'bg-white text-slate-900 shadow-sm'
                                        : 'text-slate-600'
                                }`}
                            >
                                Calendar View
                            </button>
                        </div>

                        <p className="text-xs text-slate-500">
                            {props.holidays.length} holidays total
                        </p>
                    </div>

                    {viewMode === 'list' && (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-semibold text-slate-600">
                                            Date
                                        </th>
                                        <th className="px-4 py-3 text-left font-semibold text-slate-600">
                                            Name
                                        </th>
                                        <th className="px-4 py-3 text-left font-semibold text-slate-600">
                                            Type
                                        </th>
                                        <th className="px-4 py-3 text-left font-semibold text-slate-600">
                                            Days Until
                                        </th>
                                        <th className="px-4 py-3 text-right font-semibold text-slate-600">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {props.holidays.map((holiday) => (
                                        <tr key={holiday.id}>
                                            <td className="px-4 py-3 text-slate-700">
                                                {formatDate(holiday.date)}
                                            </td>
                                            <td className="px-4 py-3 font-medium text-slate-900">
                                                {holiday.name}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className="inline-flex rounded-full bg-pink-50 px-2 py-0.5 text-xs font-semibold text-pink-600">
                                                    {holiday.type}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-slate-600">
                                                {formatDaysUntil(holiday.days_until)}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        handleDeleteHoliday(
                                                            holiday.id,
                                                        )
                                                    }
                                                    className="rounded border border-red-200 px-2 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {viewMode === 'calendar' && (
                        <div className="p-4">
                            <div className="mb-3 flex items-center justify-between">
                                <button
                                    type="button"
                                    onClick={goToPreviousMonth}
                                    className="rounded border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50"
                                >
                                    Prev
                                </button>

                                <h2 className="text-sm font-semibold text-slate-900">
                                    {monthLabels[month - 1]} {year}
                                </h2>

                                <button
                                    type="button"
                                    onClick={goToNextMonth}
                                    className="rounded border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50"
                                >
                                    Next
                                </button>
                            </div>

                            <div className="grid grid-cols-7 gap-2">
                                {weekdayLabels.map((day) => (
                                    <div
                                        key={day}
                                        className="rounded bg-slate-50 px-2 py-2 text-center text-xs font-semibold text-slate-500"
                                    >
                                        {day}
                                    </div>
                                ))}

                                {calendarCells.map((cell) => {
                                    const holiday = holidayMap.get(cell.isoDate);

                                    return (
                                        <div
                                            key={cell.isoDate}
                                            className={`min-h-22 rounded border p-2 ${
                                                cell.inCurrentMonth
                                                    ? 'border-slate-200 bg-white'
                                                    : 'border-slate-100 bg-slate-50 text-slate-400'
                                            }`}
                                        >
                                            <p className="text-xs font-semibold">
                                                {cell.dayNumber}
                                            </p>
                                            {holiday && (
                                                <div className="mt-2 rounded bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700">
                                                    {holiday.name}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {isAddOpen && (
                <div className="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4">
                    <form
                        onSubmit={handleAddHoliday}
                        className="w-full max-w-lg rounded-2xl bg-white shadow-xl"
                    >
                        <div className="border-b border-slate-200 px-5 py-4">
                            <h3 className="text-base font-semibold text-slate-900">
                                Add New Holiday
                            </h3>
                        </div>

                        <div className="space-y-4 px-5 py-4">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700">
                                    Date
                                </label>
                                <input
                                    type="date"
                                    value={data.date}
                                    onChange={(event) =>
                                        setData('date', event.target.value)
                                    }
                                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                                    required
                                />
                                {errors.date && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {errors.date}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700">
                                    Name
                                </label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(event) =>
                                        setData('name', event.target.value)
                                    }
                                    placeholder="National Mourning Day"
                                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                                    required
                                />
                                {errors.name && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {errors.name}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700">
                                    Type
                                </label>
                                <select
                                    value={data.type}
                                    onChange={(event) =>
                                        setData('type', event.target.value)
                                    }
                                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                                >
                                    <option value="public">Public</option>
                                    <option value="company">Company</option>
                                </select>
                                {errors.type && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {errors.type}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
                            <button
                                type="button"
                                onClick={() => setIsAddOpen(false)}
                                className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                {processing ? 'Saving...' : 'Add Holiday'}
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </>
    );
}
