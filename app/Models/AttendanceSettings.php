<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSettings extends Model
{
    use HasFactory;

    protected $table = 'attendance_settings';

    protected $fillable = [
        'working_hours_start',
        'working_hours_end',
        'weekend_days',
        'timezone',
        'auto_backup_enabled',
        'backup_frequency',
        'backup_path',
        'last_backup_at',
    ];

    protected $casts = [
        'weekend_days' => 'array',
        'auto_backup_enabled' => 'boolean',
        'last_backup_at' => 'datetime',
        'working_hours_start' => 'datetime:H:i',
        'working_hours_end' => 'datetime:H:i',
    ];

    /**
     * Days available for weekend selection.
     */
    public static function availableDays(): array
    {
        return ['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'];
    }

    /**
     * Helper to fetch the single settings row (singleton pattern).
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
