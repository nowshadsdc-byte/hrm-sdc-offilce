<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Leave balance for a given year (defaults to the current year): the
     * total allocation (this employee's override, or the org-wide default),
     * how many days are already approved/pending, and what's left.
     *
     * @return array{year:int,total:int,approved:int,pending:int,remaining:int,available:int}
     */
    public function leaveBalance(?int $year = null): array
    {
        $year ??= now()->year;

        $total = $this->annual_leave_days ?? AttendanceSettings::current()->default_annual_leave_days ?? 20;

        $requests = $this->leaveRequests()
            ->whereYear('start_date', $year)
            ->where('status', '!=', 'rejected')
            ->get(['status', 'start_date', 'end_date']);

        $approved = (int) $requests->where('status', 'approved')->sum('days_count');
        $pending = (int) $requests->where('status', 'pending')->sum('days_count');

        return [
            'year' => $year,
            'total' => (int) $total,
            'approved' => $approved,
            'pending' => $pending,
            'remaining' => max($total - $approved, 0),
            'available' => max($total - $approved - $pending, 0),
        ];
    }

    protected $fillable = [
        'user_id',
        'name',
        'device_user_id',
        'device_cardno',
        'shift_id',
        'annual_leave_days',
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
