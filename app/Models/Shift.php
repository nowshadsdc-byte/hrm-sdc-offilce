<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['name', 'start_time', 'end_time', 'is_default'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * The shift new/unassigned employees fall back to.
     */
    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->first()
            ?? static::query()->orderBy('id')->first();
    }

    /**
     * Human-readable time range, e.g. "10:30 AM - 6:00 PM".
     */
    public function formattedRange(): string
    {
        return Carbon::parse($this->start_time)->format('h:i A').' - '.Carbon::parse($this->end_time)->format('h:i A');
    }
}
