import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarCheck,
    CalendarDays,
    Fingerprint,
    LayoutDashboard,
    PartyPopper,
    RefreshCw,
    ShieldCheck,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { login } from '@/routes/tyro-login';

type Feature = {
    icon: LucideIcon;
    title: string;
    description: string;
};

const features: Feature[] = [
    {
        icon: LayoutDashboard,
        title: 'Real-Time Dashboard',
        description: 'Live KPIs on presence, lateness, and leave so managers always know who is working right now.',
    },
    {
        icon: CalendarCheck,
        title: 'Attendance Tracking',
        description: 'Automatic check-in and check-out records with clear status for present, late, absent, and half-day.',
    },
    {
        icon: Fingerprint,
        title: 'Biometric Devices',
        description: 'Connect fingerprint and face-recognition terminals and monitor their sync health from one place.',
    },
    {
        icon: Users,
        title: 'Employee Management',
        description: 'Centralized profiles, departments, and shift assignments for your entire workforce.',
    },
    {
        icon: CalendarDays,
        title: 'Leave Requests',
        description: 'Streamlined submission and approval workflow with a full history of every request.',
    },
    {
        icon: PartyPopper,
        title: 'Holiday Calendar',
        description: 'Company-wide holidays are reflected automatically across attendance and reports.',
    },
    {
        icon: RefreshCw,
        title: 'Automatic Sync',
        description: 'Devices push attendance logs continuously, with clear sync status and error alerts.',
    },
    {
        icon: ShieldCheck,
        title: 'Roles & Permissions',
        description: 'Fine-grained access control so admins, managers, and employees only see what they should.',
    },
];

const statusPreview = [
    { label: 'Present', badge: 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-400 dark:border-emerald-800', dot: 'bg-emerald-500' },
    { label: 'Late', badge: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/40 dark:text-amber-400 dark:border-amber-800', dot: 'bg-amber-500' },
    { label: 'On Leave', badge: 'bg-sky-100 text-sky-700 border-sky-200 dark:bg-sky-900/40 dark:text-sky-400 dark:border-sky-800', dot: 'bg-sky-500' },
    { label: 'Absent', badge: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/40 dark:text-red-400 dark:border-red-800', dot: 'bg-red-500' },
    { label: 'Half Day', badge: 'bg-violet-100 text-violet-700 border-violet-200 dark:bg-violet-900/40 dark:text-violet-400 dark:border-violet-800', dot: 'bg-violet-500' },
    { label: 'Holiday', badge: 'bg-pink-100 text-pink-700 border-pink-200 dark:bg-pink-900/40 dark:text-pink-400 dark:border-pink-800', dot: 'bg-pink-500' },
];

const stats = [
    { label: 'Employees tracked', value: '2,400+' },
    { label: 'Attendance accuracy', value: '99.6%' },
    { label: 'Devices synced', value: '150+' },
    { label: 'Platform uptime', value: '99.9%' },
];

export default function Welcome() {
    return (
        <>
            <Head title="Welcome" />

            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="sticky top-0 z-50 border-b border-border bg-background/60 backdrop-blur-md">
                    <div className="mx-auto flex h-16 max-w-[1600px] items-center justify-between px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center gap-2.5">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <Fingerprint className="h-5 w-5" />
                            </span>
                            <span className="text-xl font-bold tracking-tight">AttendPro</span>
                        </div>

                        <nav className="hidden items-center gap-8 md:flex">
                            <a href="#features" className="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">
                                Features
                            </a>
                            <a href="#status" className="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">
                                Status System
                            </a>
                        </nav>

                        <a
                            href="/login"
                            className="inline-flex h-10 items-center justify-center rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground transition-all duration-200 hover:bg-primary/90"
                        >
                            Log in
                        </a>
                    </div>
                </header>

                <main className="flex-1">
                    <section className="mx-auto max-w-[1600px] px-4 pt-16 pb-12 sm:px-6 sm:pt-24 lg:px-8">
                        <div className="mx-auto max-w-3xl text-center">
                            <span className="inline-flex items-center gap-1.5 rounded-full border border-border bg-secondary px-3 py-1 text-xs font-medium tracking-wide text-secondary-foreground">
                                <span className="h-1.5 w-1.5 rounded-full bg-primary" />
                                HRM Attendance Management
                            </span>

                            <h1 className="mt-6 text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                                Attendance management that feels effortless
                            </h1>

                            <p className="mt-4 text-base text-muted-foreground sm:text-lg">
                                AttendPro brings biometric devices, employee records, leave requests, and reporting into a single,
                                calm dashboard &mdash; so HR teams spend less time reconciling data and more time supporting people.
                            </p>

                            <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                               
                                <a
                                    href="/login"
                                    className="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-primary px-8 text-sm font-medium text-primary-foreground transition-all duration-200 hover:bg-primary/90"
                                >
                                    Get started
                                    <ArrowRight className="h-4 w-4" />
                                </a>
                            
                                <a
                                    href="#features"
                                    className="inline-flex h-11 items-center justify-center rounded-lg border border-border bg-background px-8 text-sm font-medium transition-all duration-200 hover:bg-accent hover:text-accent-foreground"
                                >
                                    Explore features
                                </a>
                            </div>
                        </div>

                        <div className="mx-auto mt-16 grid max-w-4xl grid-cols-2 gap-4 md:grid-cols-4">
                            {stats.map((stat) => (
                                <div
                                    key={stat.label}
                                    className="rounded-xl border border-border bg-card p-6 text-center text-card-foreground transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                                >
                                    <p className="text-3xl font-bold tabular-nums">{stat.value}</p>
                                    <p className="mt-1 text-xs text-muted-foreground">{stat.label}</p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section id="features" className="border-t border-border bg-muted/30">
                        <div className="mx-auto max-w-[1600px] px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                            <div className="mx-auto max-w-2xl text-center">
                                <h2 className="text-2xl font-bold tracking-tight sm:text-3xl">Everything HR needs, in one place</h2>
                                <p className="mt-3 text-sm text-muted-foreground">
                                    Every module shares the same data model, so attendance, leave, and device records always stay in
                                    sync.
                                </p>
                            </div>

                            <div className="mt-10 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                                {features.map((feature) => (
                                    <div
                                        key={feature.title}
                                        className="group rounded-xl border border-border bg-card p-6 text-card-foreground transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                                    >
                                        <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-accent text-accent-foreground">
                                            <feature.icon className="h-4.5 w-4.5" />
                                        </span>
                                        <h3 className="mt-4 text-lg font-semibold">{feature.title}</h3>
                                        <p className="mt-1.5 text-sm text-muted-foreground">{feature.description}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section id="status" className="mx-auto max-w-[1600px] px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                        <div className="rounded-xl border border-border bg-card p-6 sm:p-10">
                            <div className="flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-center">
                                <div>
                                    <h2 className="text-2xl font-bold tracking-tight sm:text-3xl">One status system, everywhere</h2>
                                    <p className="mt-2 max-w-xl text-sm text-muted-foreground">
                                        Presence states are color-coded consistently across the dashboard, reports, and exports &mdash;
                                        so anyone can scan a table and understand it instantly.
                                    </p>
                                </div>

                                <div className="flex items-center gap-2 rounded-lg border border-border bg-background px-4 py-3">
                                    <span className="relative flex h-2 w-2">
                                        <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-75" />
                                        <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
                                    </span>
                                    <span className="text-xs font-medium text-muted-foreground">Live device monitoring</span>
                                </div>
                            </div>

                            <div className="mt-8 flex flex-wrap gap-3">
                                {statusPreview.map((status) => (
                                    <span
                                        key={status.label}
                                        className={`inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-xs font-medium ${status.badge}`}
                                    >
                                        <span className={`h-1.5 w-1.5 rounded-full ${status.dot}`} />
                                        {status.label}
                                    </span>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="mx-auto max-w-[1600px] px-4 pb-16 sm:px-6 sm:pb-24 lg:px-8">
                        <div className="rounded-xl border border-border bg-primary px-6 py-12 text-center text-primary-foreground sm:px-12">
                            <h2 className="text-2xl font-bold tracking-tight sm:text-3xl">Ready to bring order to attendance?</h2>
                            <p className="mx-auto mt-3 max-w-xl text-sm text-primary-foreground/80">
                                Log in to your dashboard to review today&apos;s attendance, manage leave requests, and keep every
                                device in sync.
                            </p>
                            <a
                                href='/login'
                                className="mt-8 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-primary px-8 text-sm font-medium text-primary-foreground transition-all duration-200 hover:bg-primary/90"
                            >
                                Log in to AttendPro
                                <ArrowRight className="h-4 w-4" />
                            </a>
                        
                        </div>
                    </section>
                </main>

                <footer className="mt-auto border-t border-border bg-background/60">
                    <div className="mx-auto flex max-w-[1600px] flex-col items-center justify-between gap-4 px-4 py-8 sm:flex-row sm:px-6 lg:px-8">
                        <div className="flex items-center gap-2.5">
                            <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <Fingerprint className="h-4 w-4" />
                            </span>
                            <span className="text-sm font-semibold">AttendPro</span>
                        </div>
                        <p className="text-xs text-muted-foreground">&copy; {new Date().getFullYear()} AttendPro. All rights reserved.</p>
                    </div>
                </footer>
            </div>
        </>
    );
}
