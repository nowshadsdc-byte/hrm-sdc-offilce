import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AuditLogsTab from './components/AuditLogsTab';
import GeneralTab from './components/GeneralTab';
import HolidayCalendarTab from './components/HolidayCalendarTab';
import NotificationPreferencesTab from './components/NotificationPreferencesTab';
import ShiftsTab from './components/ShiftsTab';
import type { AttendanceSettings, Shift } from './types';

type TabKey =
    | 'general'
    | 'shifts-and-schedule'
    | 'holiday-calendar'
    | 'audit-logs'
    | 'notification-preferences';

type TabItem = {
    key: TabKey;
    label: string;
    icon: JSX.Element;
};

type AttendanceSettingsPageProps = {
    settings: AttendanceSettings;
    shifts: Shift[];
};

const tabs: TabItem[] = [
    {
        key: 'general',
        label: 'General',
        icon: (
            <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none">
                <path
                    d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7Z"
                    stroke="currentColor"
                    strokeWidth="1.8"
                />
                <path
                    d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a1.2 1.2 0 0 1 0 1.6l-1.2 1.2a1.2 1.2 0 0 1-1.6 0l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9V20a1.2 1.2 0 0 1-1.2 1.2h-1.7A1.2 1.2 0 0 1 11 20v-.2a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a1.2 1.2 0 0 1-1.6 0l-1.2-1.2a1.2 1.2 0 0 1 0-1.6l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6H4a1.2 1.2 0 0 1-1.2-1.2v-1.7A1.2 1.2 0 0 1 4 10h.2a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1l-.1-.1a1.2 1.2 0 0 1 0-1.6l1.2-1.2a1.2 1.2 0 0 1 1.6 0l.1.1a1 1 0 0 0 1.1.2 1 1 0 0 0 .6-.9V4A1.2 1.2 0 0 1 11 2.8h1.7A1.2 1.2 0 0 1 14 4v.2a1 1 0 0 0 .6.9 1 1 0 0 0 1.1-.2l.1-.1a1.2 1.2 0 0 1 1.6 0l1.2 1.2a1.2 1.2 0 0 1 0 1.6l-.1.1a1 1 0 0 0-.2 1.1 1 1 0 0 0 .9.6h.2a1.2 1.2 0 0 1 1.2 1.2v1.7a1.2 1.2 0 0 1-1.2 1.2h-.2a1 1 0 0 0-.9.6Z"
                    stroke="currentColor"
                    strokeWidth="1.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
        ),
    },
    {
        key: 'shifts-and-schedule',
        label: 'Shifts & Schedule',
        icon: (
            <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none">
                <circle cx="12" cy="12" r="8" stroke="currentColor" strokeWidth="1.8" />
                <path
                    d="M12 8v4l3 2"
                    stroke="currentColor"
                    strokeWidth="1.8"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
        ),
    },
    {
        key: 'holiday-calendar',
        label: 'Holiday Calendar',
        icon: (
            <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none">
                <rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" strokeWidth="1.8" />
                <path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
            </svg>
        ),
    },
    {
        key: 'audit-logs',
        label: 'Audit Logs',
        icon: (
            <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none">
                <path
                    d="M6 5h12M6 10h12M6 15h8"
                    stroke="currentColor"
                    strokeWidth="1.8"
                    strokeLinecap="round"
                />
                <circle cx="17" cy="15" r="2.5" stroke="currentColor" strokeWidth="1.8" />
            </svg>
        ),
    },
    {
        key: 'notification-preferences',
        label: 'Notification Preferences',
        icon: (
            <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none">
                <path
                    d="M12 4a4 4 0 0 0-4 4v2.1c0 .8-.3 1.5-.8 2.1L6 14h12l-1.2-1.8a3.6 3.6 0 0 1-.8-2.1V8a4 4 0 0 0-4-4Z"
                    stroke="currentColor"
                    strokeWidth="1.8"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
                <path d="M10 17a2 2 0 0 0 4 0" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
            </svg>
        ),
    },
];

function renderActiveTab(
    tab: TabKey,
    props: AttendanceSettingsPageProps,
): JSX.Element {
    if (tab === 'general') {
        return <GeneralTab settings={props.settings} />;
    }

    if (tab === 'shifts-and-schedule') {
        return <ShiftsTab shifts={props.shifts} />;
    }

    if (tab === 'holiday-calendar') {
        return <HolidayCalendarTab />;
    }

    if (tab === 'audit-logs') {
        return <AuditLogsTab />;
    }

    return <NotificationPreferencesTab />;
}

export default function AttendanceSettingsIndex(props: AttendanceSettingsPageProps) {
    const [activeTab, setActiveTab] = useState<TabKey>('general');

    return (
        <>
            <Head title="Settings" />

            <div className="min-h-screen bg-slate-100 px-4 py-8 md:px-8">
                <div className="mx-auto max-w-7xl">
                    <div className="mb-5">
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">
                            Settings
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Manage organization shifts, holidays, notifications, and audit visibility for AttendPro Corp.
                        </p>
                    </div>

                    <div className="mb-5 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-1 shadow-sm">
                        <div className="flex min-w-max items-center gap-1">
                            {tabs.map((tab) => (
                                <button
                                    key={tab.key}
                                    type="button"
                                    onClick={() => setActiveTab(tab.key)}
                                    className={`inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition ${
                                        activeTab === tab.key
                                            ? 'bg-slate-900 text-white shadow-sm'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                    }`}
                                >
                                    <span>{tab.icon}</span>
                                    <span>{tab.label}</span>
                                </button>
                            ))}
                        </div>
                    </div>

                    {renderActiveTab(activeTab, props)}
                </div>
            </div>
        </>
    );
}
