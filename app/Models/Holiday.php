<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'name',
        'type',
        'days_until',
    ];

    protected $casts = [
        'date' => 'date',
        'days_until' => 'integer',
    ];
}
