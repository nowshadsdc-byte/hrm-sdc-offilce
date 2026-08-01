<?php

use App\Http\Controllers\LeaveRequestController;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

it('renders the self leave requests page without blade syntax errors', function () {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });

    Schema::create('employees', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable();
        $table->string('name');
        $table->string('status')->default('active');
        $table->timestamps();
    });

    Schema::create('attendance_settings', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('default_annual_leave_days')->default(20);
        $table->timestamps();
    });

    Schema::create('leave_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id');
        $table->foreignId('employee_id');
        $table->string('leave_type');
        $table->date('start_date');
        $table->date('end_date');
        $table->text('reason')->nullable();
        $table->string('status')->default('pending');
        $table->timestamps();
    });

    $user = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane2@example.com',
        'password' => bcrypt('secret123'),
    ]);

    Employee::create([
        'user_id' => $user->id,
        'name' => 'Jane Doe',
        'status' => 'active',
    ]);

    $request = Request::create('/dashboard/myleaverequests', 'GET');
    $request->setUserResolver(fn () => $user);

    $response = app(LeaveRequestController::class)->myRequests($request);
    $rendered = $response->render();

    expect($rendered)->toContain('My Leave Requests');
});

it('stores a leave request for the authenticated employee only', function () {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });

    Schema::create('employees', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable();
        $table->string('name');
        $table->string('status')->default('active');
        $table->timestamps();
    });

    Schema::create('attendance_settings', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('default_annual_leave_days')->default(20);
        $table->timestamps();
    });

    Schema::create('leave_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id');
        $table->foreignId('employee_id');
        $table->string('leave_type');
        $table->date('start_date');
        $table->date('end_date');
        $table->text('reason')->nullable();
        $table->string('status')->default('pending');
        $table->timestamps();
    });

    $user = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => bcrypt('secret123'),
    ]);

    $employee = Employee::create([
        'user_id' => $user->id,
        'name' => 'Jane Doe',
        'status' => 'active',
    ]);

    $request = Request::create('/leave-requests/my', 'POST', [
        'employee_id' => 999,
        'leave_type' => 'Casual Leave',
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-03',
        'reason' => '<strong>Need time off for family matters</strong>',
    ]);
    $request->setUserResolver(fn () => $user);

    $controller = app(LeaveRequestController::class);
    $response = $controller->storeForSelf($request);

    expect($response->getSession()->get('success'))->toContain('submitted');

    $leaveRequest = LeaveRequest::latest()->first();

    expect($leaveRequest)->not()->toBeNull();
    expect($leaveRequest->user_id)->toBe($user->id);
    expect($leaveRequest->employee_id)->toBe($employee->id);
    expect($leaveRequest->reason)->toBe('<strong>Need time off for family matters</strong>');
});
