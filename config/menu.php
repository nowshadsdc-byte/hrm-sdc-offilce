<?php

return [
    'adminMenuItems' => [
        [
            'title' => 'Employees',
            'url' => '/employees',
            'route' => 'employees.index',
            'roles' => config('tyro-dashboard.admin_roles', ['admin', 'super-admin']),
            'visibility' => 'any',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 0 0-3-3.87" /><path stroke-linecap="round" stroke-linejoin="round" d="M16 3.13a4 4 0 0 1 0 7.75" /></svg>',
        ],
        [
            'title' => 'Devices',
            'url' => '/dashboard/devices',
            'route' => 'dashboard.devices',
            'roles' => config('tyro-dashboard.admin_roles', ['admin', 'super-admin']),
            'visibility' => 'any',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>',
        ],
        [
            'title' => 'Attendances',
            'url' => '/attendances',
            'route' => 'attendances.index',
            'roles' => config('tyro-dashboard.admin_roles', ['admin', 'super-admin']),
            'visibility' => 'any',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        ],
        [
            'title' => 'Leave Requests',
            'url' => '/leave-requests',
            'route' => 'leave-requests.index',
            'roles' => config('tyro-dashboard.admin_roles', ['admin', 'super-admin']),
            'visibility' => 'any',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h6M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>',
        ],
        [
            'title' => 'Holiday Calendar',
            'url' => '/holiday-calendar',
            'route' => 'holiday-calendar.index',
            'roles' => config('tyro-dashboard.admin_roles', ['admin', 'super-admin']),
            'privileges' => ['holiday-calendar.access'],
            'visibility' => 'any',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18"/></svg>',
        ],
        [
            'title' => 'Attendance Settings',
            'url' => '/attendance-settings',
            'route' => 'attendance-settings.index',
            'roles' => config('tyro-dashboard.admin_roles', ['admin', 'super-admin']),
            'privileges' => ['attendance-settings.access'],
            'visibility' => 'any',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-4.88-8" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.4 4.6l.6 1.4 1.4.6-1.4.6-.6 1.4-.6-1.4-1.4-.6 1.4-.6.6-1.4z" /></svg>',
        ],
    ],

    'commonMenuItems' => [],

    'userMenuItems' => [],
];
