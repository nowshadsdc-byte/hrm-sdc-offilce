<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'name',
        'nid',
        'dob',
        'address',
        'phone',
        'job_title',
        'job_join_date',
        'job_description',
        'nid_file',
        'certificate_file',
        'contract_file',
        'department',
        'designation',
        'role',
        'status',
        'transfer_promotion_notes',
        'separation_type',
        'separation_date',
        'clearance_completed',
    ];

    protected $casts = [
        'dob' => 'date',
        'job_join_date' => 'date',
        'separation_date' => 'date',
        'clearance_completed' => 'boolean',
    ];
}
