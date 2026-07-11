<?php

return [
    'schedule' => [
        'office_start' => env('ATTENDANCE_OFFICE_START', '09:00:00'),
        'office_start_late_threshold' => env('ATTENDANCE_LATE_THRESHOLD', '10:30:00'),
        'lunch_start' => env('ATTENDANCE_LUNCH_START', '13:00:00'),
        'lunch_end' => env('ATTENDANCE_LUNCH_END', '15:00:00'),
        'office_end_start' => env('ATTENDANCE_OFFICE_END_START', '17:30:00'),
        'office_end_end' => env('ATTENDANCE_OFFICE_END_END', '18:30:00'),
    ],
];
