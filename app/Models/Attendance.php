<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'user_id',
        'device_id',
        'check_in',
        'lunch_start',
        'lunch_end',
        'check_out',
        'lunch_duration_minutes',
        'total_work_minutes',
        'overtime_minutes',
        'late_status',
        'late_duration_minutes',
        'remarks',
        'date',
        'device_user_id',
        'employee_name',
        'record_time',
        'record_date',
        'record_time_only',
        'last_raw_punch_at',
        'raw_punch_signature',
        'last_synced_at',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'lunch_start' => 'datetime',
        'lunch_end' => 'datetime',
        'check_out' => 'datetime',
        'date' => 'date',
        'record_time' => 'datetime',
        'last_raw_punch_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'late_status' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
