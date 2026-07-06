import { Head, Link } from '@inertiajs/react';
import attendances from '@/routes/attendances';
import type { AttendanceRecord } from './types';

type ShowAttendanceProps = {
    attendance: AttendanceRecord;
};

export default function AttendancesShow({ attendance }: ShowAttendanceProps) {
    return (
        <>
            <Head title={`Attendance #${attendance.id}`} />

            <div className="mx-auto max-w-3xl space-y-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold text-slate-900">Attendance #{attendance.id}</h1>
                    <div className="flex gap-3 text-sm">
                        <Link href={attendances.edit.url(attendance.id)} className="text-slate-700 hover:underline">
                            Edit
                        </Link>
                        <Link href={attendances.index.url()} className="text-slate-700 hover:underline">
                            Back to list
                        </Link>
                    </div>
                </div>

                <div className="space-y-3 rounded-xl border border-slate-200 bg-white p-4 text-sm">
                    <div className="flex justify-between border-b border-slate-100 pb-2">
                        <span className="text-slate-500">Date</span>
                        <span className="font-medium text-slate-900">{attendance.date}</span>
                    </div>
                    <div className="flex justify-between border-b border-slate-100 pb-2">
                        <span className="text-slate-500">Employee</span>
                        <span className="font-medium text-slate-900">{attendance.employee?.user?.name ?? 'N/A'}</span>
                    </div>
                    <div className="flex justify-between border-b border-slate-100 pb-2">
                        <span className="text-slate-500">Check In</span>
                        <span className="font-medium text-slate-900">{attendance.check_in}</span>
                    </div>
                    <div className="flex justify-between border-b border-slate-100 pb-2">
                        <span className="text-slate-500">Check Out</span>
                        <span className="font-medium text-slate-900">{attendance.check_out ?? 'N/A'}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-slate-500">Device</span>
                        <span className="font-medium text-slate-900">{attendance.device?.name ?? 'N/A'}</span>
                    </div>
                </div>
            </div>
        </>
    );
}
