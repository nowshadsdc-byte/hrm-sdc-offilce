<?php

namespace App\Models;

use Carbon\Carbon;
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
        'early_leave_status',
        'early_leave_minutes',
        'remarks',
        'date',
        'device_user_id',
        'employee_name',
        'record_time',
        'record_date',
        'record_time_only',
        'timezone',
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
        'early_leave_status' => 'boolean',
    ];

    /**
     * These accessors expose the stored times exactly as the server recorded them (UTC),
     * regardless of the application's configured timezone, so no conversion ever occurs.
     */
    public function getCheckInUtcAttribute(): ?Carbon
    {
        return $this->rawUtc($this->getRawOriginal('check_in'));
    }

    public function getCheckOutUtcAttribute(): ?Carbon
    {
        return $this->rawUtc($this->getRawOriginal('check_out'));
    }

    protected function rawUtc(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d H:i:s', $value, 'UTC');
    }

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
