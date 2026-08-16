<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected $fillable = [
        'employee_id',
        'uploaded_by',
        'title',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
    ];
}
