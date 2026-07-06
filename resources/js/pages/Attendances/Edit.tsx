import { Head, Link, useForm } from '@inertiajs/react';
import attendances from '@/routes/attendances';
import type { AttendanceRecord, DeviceOption, EmployeeOption } from './types';

type EditAttendanceProps = {
    attendance: AttendanceRecord;
    employees: EmployeeOption[];
    devices: DeviceOption[];
};

export default function AttendancesEdit({ attendance, employees, devices }: EditAttendanceProps) {
    const { data, setData, put, processing, errors } = useForm({
        employee_id: String(attendance.employee_id ?? ''),
        user_id: String(attendance.user_id ?? ''),
        device_id: attendance.device_id ? String(attendance.device_id) : '',
        check_in: attendance.check_in ?? '',
        check_out: attendance.check_out ?? '',
        date: attendance.date ?? '',
    });

    const onEmployeeChange = (employeeId: string) => {
        const selected = employees.find((employee) => String(employee.id) === employeeId);

        setData('employee_id', employeeId);
        setData('user_id', selected?.user_id ? String(selected.user_id) : '');
    };

    return (
        <>
            <Head title="Edit Attendance" />

            <div className="mx-auto max-w-3xl space-y-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold text-slate-900">Edit Attendance</h1>
                    <Link href={attendances.index.url()} className="text-sm text-slate-700 hover:underline">
                        Back to list
                    </Link>
                </div>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        put(attendances.update.url(attendance.id));
                    }}
                    className="space-y-4 rounded-xl border border-slate-200 bg-white p-4"
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        <label className="space-y-1 text-sm">
                            <span className="text-slate-700">Employee</span>
                            <select
                                value={data.employee_id}
                                onChange={(event) => onEmployeeChange(event.target.value)}
                                className="w-full rounded-md border border-slate-300 px-3 py-2"
                            >
                                <option value="">Select employee</option>
                                {employees.map((employee) => (
                                    <option key={employee.id} value={employee.id}>
                                        {employee.user?.name ?? `Employee #${employee.id}`}
                                    </option>
                                ))}
                            </select>
                            {errors.employee_id ? <p className="text-xs text-red-600">{errors.employee_id}</p> : null}
                        </label>

                        <label className="space-y-1 text-sm">
                            <span className="text-slate-700">Device</span>
                            <select
                                value={data.device_id}
                                onChange={(event) => setData('device_id', event.target.value)}
                                className="w-full rounded-md border border-slate-300 px-3 py-2"
                            >
                                <option value="">No device</option>
                                {devices.map((device) => (
                                    <option key={device.id} value={device.id}>
                                        {device.name}
                                    </option>
                                ))}
                            </select>
                            {errors.device_id ? <p className="text-xs text-red-600">{errors.device_id}</p> : null}
                        </label>

                        <label className="space-y-1 text-sm">
                            <span className="text-slate-700">Date</span>
                            <input
                                type="date"
                                value={data.date}
                                onChange={(event) => setData('date', event.target.value)}
                                className="w-full rounded-md border border-slate-300 px-3 py-2"
                            />
                            {errors.date ? <p className="text-xs text-red-600">{errors.date}</p> : null}
                        </label>

                        <label className="space-y-1 text-sm">
                            <span className="text-slate-700">Check In</span>
                            <input
                                type="text"
                                value={data.check_in}
                                onChange={(event) => setData('check_in', event.target.value)}
                                placeholder="YYYY-MM-DD HH:MM:SS"
                                className="w-full rounded-md border border-slate-300 px-3 py-2"
                            />
                            {errors.check_in ? <p className="text-xs text-red-600">{errors.check_in}</p> : null}
                        </label>

                        <label className="space-y-1 text-sm md:col-span-2">
                            <span className="text-slate-700">Check Out</span>
                            <input
                                type="text"
                                value={data.check_out}
                                onChange={(event) => setData('check_out', event.target.value)}
                                placeholder="YYYY-MM-DD HH:MM:SS"
                                className="w-full rounded-md border border-slate-300 px-3 py-2"
                            />
                            {errors.check_out ? <p className="text-xs text-red-600">{errors.check_out}</p> : null}
                        </label>
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
                    >
                        {processing ? 'Saving...' : 'Update Attendance'}
                    </button>
                </form>
            </div>
        </>
    );
}
