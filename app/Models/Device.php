<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'name',
        'device_type',
        'connection_type',
        'ip_address',
        'api_endpoint',
        'api_url',
        'port',
        'serial_number',
        'location',
        'sync_interval_minutes',
        'auto_sync',
        'status',
        'last_sync_at',
        'last_error',
    ];

    protected $casts = [
        'port' => 'integer',
        'sync_interval_minutes' => 'integer',
        'auto_sync' => 'boolean',
        'last_sync_at' => 'datetime',
    ];
}
