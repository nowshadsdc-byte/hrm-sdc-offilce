import { Head, Link, router } from '@inertiajs/react';
import attendances from '@/routes/attendances';
import type {
    AttendanceRecord,
    DeviceOption,
    EmployeeOption,
    PaginatedAttendances,
} from './types';

type AttendanceIndexProps = {
    attendances: PaginatedAttendances;
    employees: EmployeeOption[];
    devices: DeviceOption[];
    filters: {
        start_date?: string;
        end_date?: string;
        employee_id?: number | string;
        device_id?: number | string;
    };
};

function employeeName(attendance: AttendanceRecord): string {
    return attendance.employee?.user?.name ?? 'N/A';
}

export default function AttendancesIndex({
    attendances: paginated,
    employees,
    devices,
    filters,
}: AttendanceIndexProps) {
    return (
        <>
            <Head title="Attendances" />

            <div className="mx-auto max-w-7xl space-y-4 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-900">Attendances</h1>
                        <p className="text-sm text-slate-500">Track and manage employee attendance records.</p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <a
                            href={attendances.export.url({
                                query: {
                                    start_date: filters.start_date,
                                    end_date: filters.end_date,
                                    employee_id: filters.employee_id,
                                },
                            })}
                            className="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Export CSV
                        </a>
                        <Link
                            href={attendances.create.url()}
                            className="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800"
                        >
                            Add Attendance
                        </Link>
                    </div>
                </div>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        const form = new FormData(event.currentTarget);
                        router.get(attendances.index.url(), {
                            start_date: (form.get('start_date') ?? '').toString(),
                            end_date: (form.get('end_date') ?? '').toString(),
                            employee_id: (form.get('employee_id') ?? '').toString(),
                            device_id: (form.get('device_id') ?? '').toString(),
                        });
                    }}
                    className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-5"
                >
                    <input
                        name="start_date"
                        type="date"
                        defaultValue={filters.start_date ?? ''}
                        className="rounded-md border border-slate-300 px-3 py-2 text-sm"
                    />
                    <input
                        name="end_date"
                        type="date"
                        defaultValue={filters.end_date ?? ''}
                        className="rounded-md border border-slate-300 px-3 py-2 text-sm"
                    />
                    <select
                        name="employee_id"
                        defaultValue={String(filters.employee_id ?? '')}
                        className="rounded-md border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">All employees</option>
                        {employees.map((employee) => (
                            <option key={employee.id} value={employee.id}>
                                {employee.user?.name ?? `Employee #${employee.id}`}
                            </option>
                        ))}
                    </select>
                    <select
                        name="device_id"
                        defaultValue={String(filters.device_id ?? '')}
                        className="rounded-md border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">All devices</option>
                        {devices.map((device) => (
                            <option key={device.id} value={device.id}>
                                {device.name}
                            </option>
                        ))}
                    </select>
                    <button
                        type="submit"
                        className="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800"
                    >
                        Apply Filters
                    </button>
                </form>

                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <p className="border-b border-slate-100 px-4 py-2 text-xs text-slate-500 md:hidden">
                        Swipe horizontally to view all columns.
                    </p>
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-190 text-sm">
                            <thead className="bg-slate-50 text-left text-slate-600">
                                <tr>
                                    <th className="whitespace-nowrap px-4 py-3">Date</th>
                                    <th className="px-4 py-3">Employee</th>
                                    <th className="whitespace-nowrap px-4 py-3">Check In</th>
                                    <th className="whitespace-nowrap px-4 py-3">Check Out</th>
                                    <th className="px-4 py-3">Device</th>
                                    <th className="whitespace-nowrap px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {paginated.data.length === 0 ? (
                                    <tr>
                                        <td className="px-4 py-6 text-center text-slate-500" colSpan={6}>
                                            No attendance records found.
                                        </td>
                                    </tr>
                                ) : (
                                    paginated.data.map((attendance) => (
                                        <tr key={attendance.id} className="border-t border-slate-100">
                                            <td className="whitespace-nowrap px-4 py-3">{attendance.date}</td>
                                            <td className="px-4 py-3">{employeeName(attendance)}</td>
                                            <td className="whitespace-nowrap px-4 py-3">{attendance.check_in}</td>
                                            <td className="whitespace-nowrap px-4 py-3">{attendance.check_out ?? 'N/A'}</td>
                                            <td className="px-4 py-3">{attendance.device?.name ?? 'N/A'}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-3">
                                                    <Link href={attendances.show.url(attendance.id)} className="text-slate-700 hover:underline">
                                                        View
                                                    </Link>
                                                    <Link href={attendances.edit.url(attendance.id)} className="text-slate-700 hover:underline">
                                                        Edit
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            if (!confirm('Delete this attendance record?')) {
                                                                return;
                                                            }
                                                            router.delete(attendances.destroy.url(attendance.id));
                                                        }}
                                                        className="text-red-600 hover:underline"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="flex items-center justify-between text-sm text-slate-600">
                    <p>
                        Showing {paginated.data.length} of {paginated.total} records
                    </p>
                    <p>
                        Page {paginated.current_page} of {paginated.last_page}
                    </p>
                </div>
            </div>
        </>
    );
}
