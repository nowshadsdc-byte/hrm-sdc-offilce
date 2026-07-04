import { router, useForm } from '@inertiajs/react';
import {
    backupNow,
    restore,
    update,
} from '@/actions/App/Http/Controllers/AttendanceSettingsController';
import { useRef } from 'react';
import type { AttendanceSettings } from '../types';

type GeneralTabProps = {
    settings: AttendanceSettings;
};

const weekendOptions = [
    { value: 'sat', label: 'Sat' },
    { value: 'sun', label: 'Sun' },
    { value: 'mon', label: 'Mon' },
    { value: 'tue', label: 'Tue' },
    { value: 'wed', label: 'Wed' },
    { value: 'thu', label: 'Thu' },
    { value: 'fri', label: 'Fri' },
] as const;

const timezoneOptions = [
    'Asia/Dhaka',
    'Asia/Kolkata',
    'Asia/Karachi',
    'UTC',
    'Europe/London',
    'America/New_York',
];

const backupFrequencies = [
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
] as const;

function formatLastBackup(lastBackupAt: string | null): string {
    if (!lastBackupAt) {
        return 'No backups yet';
    }

    const date = new Date(lastBackupAt);
    if (Number.isNaN(date.getTime())) {
        return lastBackupAt;
    }

    return date.toLocaleString();
}

export default function GeneralTab({ settings }: GeneralTabProps) {
    const restoreInputRef = useRef<HTMLInputElement | null>(null);

    const { data, setData, put, processing, errors } = useForm({
        working_hours_start: settings.working_hours_start,
        working_hours_end: settings.working_hours_end,
        weekend_days: settings.weekend_days,
        timezone: settings.timezone || 'Asia/Dhaka',
        auto_backup_enabled: settings.auto_backup_enabled,
        backup_frequency: settings.backup_frequency ?? 'weekly',
        backup_path: settings.backup_path ?? 'storage/app/backups',
    });

    const handleWeekendChange = (day: string) => {
        const isSelected = data.weekend_days.includes(day);
        const nextValue = isSelected
            ? data.weekend_days.filter((item) => item !== day)
            : [...data.weekend_days, day];

        setData('weekend_days', nextValue);
    };

    const handleSave = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        put(update.url(), {
            preserveScroll: true,
        });
    };

    const handleExport = () => {
        router.post(
            backupNow.url(),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const handleRestoreClick = () => {
        restoreInputRef.current?.click();
    };

    const handleRestoreFile = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        router.post(
            restore.url(),
            { backup_file: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => {
                    if (restoreInputRef.current) {
                        restoreInputRef.current.value = '';
                    }
                },
            },
        );
    };

    return (
        <div className="space-y-6">
            <form
                onSubmit={handleSave}
                className="rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div className="border-b border-slate-200 px-6 py-5">
                    <h2 className="text-base font-semibold text-slate-900">
                        Company Information
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Manage organization hours and backup preferences.
                    </p>
                </div>

                <div className="space-y-6 px-6 py-5">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <label className="mb-2 block text-sm font-medium text-slate-700">
                                Working Hours Start
                            </label>
                            <input
                                type="time"
                                value={data.working_hours_start}
                                onChange={(event) =>
                                    setData(
                                        'working_hours_start',
                                        event.target.value,
                                    )
                                }
                                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                            />
                            {errors.working_hours_start && (
                                <p className="mt-1 text-xs text-red-600">
                                    {errors.working_hours_start}
                                </p>
                            )}
                        </div>

                        <div>
                            <label className="mb-2 block text-sm font-medium text-slate-700">
                                Working Hours End
                            </label>
                            <input
                                type="time"
                                value={data.working_hours_end}
                                onChange={(event) =>
                                    setData('working_hours_end', event.target.value)
                                }
                                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                            />
                            {errors.working_hours_end && (
                                <p className="mt-1 text-xs text-red-600">
                                    {errors.working_hours_end}
                                </p>
                            )}
                        </div>
                    </div>

                    <div>
                        <p className="mb-2 block text-sm font-medium text-slate-700">
                            Weekend Configuration
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {weekendOptions.map((day) => (
                                <label
                                    key={day.value}
                                    className="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 px-3 py-1.5 text-sm text-slate-700 transition hover:border-slate-400"
                                >
                                    <input
                                        type="checkbox"
                                        checked={data.weekend_days.includes(
                                            day.value,
                                        )}
                                        onChange={() =>
                                            handleWeekendChange(day.value)
                                        }
                                        className="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                    />
                                    {day.label}
                                </label>
                            ))}
                        </div>
                        {errors.weekend_days && (
                            <p className="mt-1 text-xs text-red-600">
                                {errors.weekend_days}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <label className="mb-2 block text-sm font-medium text-slate-700">
                                Timezone
                            </label>
                            <select
                                value={data.timezone}
                                onChange={(event) =>
                                    setData('timezone', event.target.value)
                                }
                                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                            >
                                {timezoneOptions.map((zone) => (
                                    <option key={zone} value={zone}>
                                        {zone}
                                    </option>
                                ))}
                            </select>
                            {errors.timezone && (
                                <p className="mt-1 text-xs text-red-600">
                                    {errors.timezone}
                                </p>
                            )}
                        </div>

                        <div>
                            <label className="mb-2 block text-sm font-medium text-slate-700">
                                Backup Frequency
                            </label>
                            <select
                                value={data.backup_frequency ?? ''}
                                disabled={!data.auto_backup_enabled}
                                onChange={(event) =>
                                    setData(
                                        'backup_frequency',
                                        event.target.value as
                                            | 'daily'
                                            | 'weekly'
                                            | 'monthly',
                                    )
                                }
                                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none transition disabled:cursor-not-allowed disabled:bg-slate-100 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                            >
                                {backupFrequencies.map((frequency) => (
                                    <option
                                        key={frequency.value}
                                        value={frequency.value}
                                    >
                                        {frequency.label}
                                    </option>
                                ))}
                            </select>
                            {errors.backup_frequency && (
                                <p className="mt-1 text-xs text-red-600">
                                    {errors.backup_frequency}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <label className="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={data.auto_backup_enabled}
                                    onChange={(event) =>
                                        setData(
                                            'auto_backup_enabled',
                                            event.target.checked,
                                        )
                                    }
                                    className="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                />
                                Auto Backup Enabled
                            </label>
                            {errors.auto_backup_enabled && (
                                <p className="mt-1 text-xs text-red-600">
                                    {errors.auto_backup_enabled}
                                </p>
                            )}
                        </div>

                        <div>
                            <label className="mb-2 block text-sm font-medium text-slate-700">
                                Backup Path
                            </label>
                            <input
                                type="text"
                                value={data.backup_path}
                                onChange={(event) =>
                                    setData('backup_path', event.target.value)
                                }
                                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                            />
                            {errors.backup_path && (
                                <p className="mt-1 text-xs text-red-600">
                                    {errors.backup_path}
                                </p>
                            )}
                        </div>
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-slate-700">
                            Last Backup At
                        </label>
                        <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                            {formatLastBackup(settings.last_backup_at)}
                        </div>
                    </div>
                </div>

                <div className="flex justify-end border-t border-slate-200 px-6 py-4">
                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        {processing ? 'Saving...' : 'Save Changes'}
                    </button>
                </div>
            </form>

            <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-200 px-6 py-5">
                    <h2 className="text-base font-semibold text-slate-900">
                        Backup & Restore
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Export the database or restore from a previous backup.
                    </p>
                </div>

                <div className="flex flex-wrap gap-3 px-6 py-5">
                    <button
                        type="button"
                        onClick={handleExport}
                        className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Export Database
                    </button>

                    <button
                        type="button"
                        onClick={handleRestoreClick}
                        className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Restore from file
                    </button>

                    <input
                        ref={restoreInputRef}
                        type="file"
                        accept=".zip,.sql"
                        onChange={handleRestoreFile}
                        className="hidden"
                    />
                </div>
            </div>
        </div>
    );
}
