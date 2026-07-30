<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawDeviceData extends Model
{
    protected $table = 'rawDeviceData';

    protected $fillable = [
        'deviceUserId',
        'employeeName',
        'date',
        'time',
        'recordTime',
        'timeZone',
        'uniqueKey',
    ];
}
