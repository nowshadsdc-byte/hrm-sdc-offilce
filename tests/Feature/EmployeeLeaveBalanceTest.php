<?php

use App\Http\Controllers\EmployeeController;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

function createLeaveBalanceSchema(): void
{
    Schema::create('shifts', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->time('start_time');
        $table->time('end_time');
        $table->boolean('is_default')->default(false);
        $table->timestamps();
    });

    Schema::create('employees', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable();
        $table->string('name')->nullable();
        $table->string('device_user_id')->nullable();
        $table->foreignId('shift_id')->nullable();
        $table->unsignedInteger('annual_leave_days')->nullable();
        $table->string('status')->default('active');
        $table->boolean('clearance_completed')->default(false);
        $table->timestamps();
    });

    Schema::create('attendance_settings', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('default_annual_leave_days')->default(20);
        $table->timestamps();
    });

    Schema::create('leave_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('employee_id')->nullable();
        $table->string('leave_type')->nullable();
        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();
        $table->text('reason')->nullable();
        $table->string('status')->default('pending');
        $table->foreignId('approved_by')->nullable();
        $table->timestamp('approved_at')->nullable();
        $table->text('rejection_reason')->nullable();
        $table->timestamps();
    });
}

function makeAdminUser(): object
{
    return new class
    {
        public function hasAnyRole(array $roles): bool
        {
            return true;
        }
    };
}

function makeNonAdminUser(): object
{
    return new class
    {
        public function hasAnyRole(array $roles): bool
        {
            return false;
        }
    };
}

it('uses the org-wide default leave allocation when the employee has no override', function () {
    createLeaveBalanceSchema();

    $employee = Employee::create(['name' => 'Default Leave Employee']);

    expect($employee->leaveBalance(2026)['total'])->toBe(20);
});

it('uses a per-employee leave allocation override instead of the default', function () {
    createLeaveBalanceSchema();

    $employee = Employee::create(['name' => 'Custom Leave Employee', 'annual_leave_days' => 30]);

    expect($employee->leaveBalance(2026)['total'])->toBe(30);
});

it('computes approved, pending, remaining, and available days for the given year, ignoring rejected requests', function () {
    createLeaveBalanceSchema();

    $employee = Employee::create(['name' => 'Balance Employee', 'annual_leave_days' => 20]);

    LeaveRequest::create([
        'employee_id' => $employee->id,
        'leave_type' => 'Annual Leave',
        'start_date' => '2026-02-01',
        'end_date' => '2026-02-05',
        'status' => 'approved',
    ]);

    LeaveRequest::create([
        'employee_id' => $employee->id,
        'leave_type' => 'Casual Leave',
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-02',
        'status' => 'pending',
    ]);

    LeaveRequest::create([
        'employee_id' => $employee->id,
        'leave_type' => 'Sick Leave',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-10',
        'status' => 'rejected',
    ]);

    // Outside the requested year - should not count.
    LeaveRequest::create([
        'employee_id' => $employee->id,
        'leave_type' => 'Annual Leave',
        'start_date' => '2025-06-01',
        'end_date' => '2025-06-01',
        'status' => 'approved',
    ]);

    $balance = $employee->leaveBalance(2026);

    expect($balance['total'])->toBe(20);
    expect($balance['approved'])->toBe(5);
    expect($balance['pending'])->toBe(2);
    expect($balance['remaining'])->toBe(15);
    expect($balance['available'])->toBe(13);
});

it('only allows an admin to set a custom leave allocation for an employee', function () {
    createLeaveBalanceSchema();

    $employee = Employee::create(['name' => 'Update Target', 'status' => 'active']);
    $controller = app(EmployeeController::class);

    $payload = [
        'name' => 'Update Target',
        'status' => 'active',
        'clearance_completed' => '0',
        'annual_leave_days' => '25',
    ];

    $nonAdminRequest = Request::create('/employees/'.$employee->id, 'PUT', $payload);
    $nonAdminRequest->setUserResolver(fn () => makeNonAdminUser());

    $controller->update($nonAdminRequest, $employee);

    expect($employee->fresh()->annual_leave_days)->toBeNull();

    $adminRequest = Request::create('/employees/'.$employee->id, 'PUT', $payload);
    $adminRequest->setUserResolver(fn () => makeAdminUser());

    $controller->update($adminRequest, $employee);

    expect($employee->fresh()->annual_leave_days)->toBe(25);
});
