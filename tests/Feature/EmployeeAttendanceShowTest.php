<?php

use App\Http\Controllers\EmployeeController;
use App\Models\AttendanceSettings;
use App\Models\Employee;
use App\Models\RawDeviceData;
use App\Services\AttendanceSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

function createEmployeeShowSchema(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->timestamps();
    });

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
        $table->date('job_join_date')->nullable();
        $table->timestamps();
    });

    Schema::create('attendances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('employee_id')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->string('employee_name')->nullable();
        $table->string('device_user_id')->nullable();
        $table->date('date')->nullable();
        $table->dateTime('check_in')->nullable();
        $table->dateTime('lunch_start')->nullable();
        $table->dateTime('lunch_end')->nullable();
        $table->dateTime('check_out')->nullable();
        $table->integer('lunch_duration_minutes')->nullable();
        $table->integer('total_work_minutes')->nullable();
        $table->integer('overtime_minutes')->nullable();
        $table->boolean('late_status')->default(false);
        $table->integer('late_duration_minutes')->nullable();
        $table->boolean('early_leave_status')->default(false);
        $table->integer('early_leave_minutes')->default(0);
        $table->string('remarks')->nullable();
        $table->dateTime('record_time')->nullable();
        $table->date('record_date')->nullable();
        $table->string('record_time_only')->nullable();
        $table->string('timezone')->nullable();
        $table->dateTime('last_raw_punch_at')->nullable();
        $table->string('raw_punch_signature')->nullable();
        $table->dateTime('last_synced_at')->nullable();
        $table->timestamps();
    });

    Schema::create('rawDeviceData', function (Blueprint $table) {
        $table->id();
        $table->string('deviceUserId')->nullable();
        $table->string('employeeName')->nullable();
        $table->date('date')->nullable();
        $table->time('time')->nullable();
        $table->dateTime('recordTime')->nullable();
        $table->string('timeZone')->nullable();
        $table->string('uniqueKey')->nullable();
        $table->timestamps();
    });

    Schema::create('attendance_settings', function (Blueprint $table) {
        $table->id();
        $table->time('working_hours_start')->nullable();
        $table->time('working_hours_end')->nullable();
        $table->json('weekend_days')->nullable();
        $table->string('timezone')->nullable();
        $table->boolean('auto_backup_enabled')->default(false);
        $table->string('backup_frequency')->nullable();
        $table->string('backup_path')->nullable();
        $table->dateTime('last_backup_at')->nullable();
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

it('filters an employee\'s attendance by month', function () {
    createEmployeeShowSchema();

    $employee = Employee::create(['name' => 'Jane Doe', 'device_user_id' => 'DU1']);

    $employee->attendances()->create([
        'date' => '2026-06-15',
        'check_in' => '2026-06-15 10:00:00',
        'total_work_minutes' => 480,
    ]);

    $employee->attendances()->create([
        'date' => '2026-07-15',
        'check_in' => '2026-07-15 10:00:00',
        'total_work_minutes' => 480,
    ]);

    $controller = app(EmployeeController::class);
    $request = Request::create('/employees/'.$employee->id, 'GET', ['month' => '2026-07']);

    $response = $controller->show($request, $employee, app(AttendanceSyncService::class));
    $data = $response->getData();

    // Every calendar day in the filtered range is now represented (present,
    // weekend, or absent), not just days with an actual punch.
    $matchingRow = $data['attendances']->getCollection()
        ->first(fn ($row) => $row->date->toDateString() === '2026-07-15');

    expect($matchingRow)->not->toBeNull();
    expect($matchingRow->status)->toBe('present');
    expect($data['startDate'])->toBe('2026-07-01');
    expect($data['endDate'])->toBe('2026-07-31');
});

it('filters an employee\'s attendance by a custom date range', function () {
    createEmployeeShowSchema();

    $employee = Employee::create(['name' => 'John Roe', 'device_user_id' => 'DU2']);

    $employee->attendances()->create([
        'date' => '2026-07-10',
        'check_in' => '2026-07-10 10:00:00',
        'total_work_minutes' => 480,
    ]);

    $employee->attendances()->create([
        'date' => '2026-07-20',
        'check_in' => '2026-07-20 11:00:00',
        'total_work_minutes' => 420,
        'late_status' => true,
    ]);

    $controller = app(EmployeeController::class);
    $request = Request::create('/employees/'.$employee->id, 'GET', [
        'start_date' => '2026-07-18',
        'end_date' => '2026-07-25',
    ]);

    $response = $controller->show($request, $employee, app(AttendanceSyncService::class));
    $data = $response->getData();

    $matchingRow = $data['attendances']->getCollection()
        ->first(fn ($row) => $row->date->toDateString() === '2026-07-20');

    expect($matchingRow)->not->toBeNull();
    expect($matchingRow->status)->toBe('present');
    expect($data['stats']['late'])->toBe(1);
});

it('excludes attendance before the employee join date from history statistics', function () {
    createEmployeeShowSchema();

    $employee = Employee::create([
        'name' => 'Mid-Month Join Employee',
        'device_user_id' => 'DU6',
        'job_join_date' => '2026-07-15',
    ]);

    $employee->attendances()->create([
        'date' => '2026-07-10',
        'check_in' => '2026-07-10 10:00:00',
        'total_work_minutes' => 480,
    ]);

    $employee->attendances()->create([
        'date' => '2026-07-20',
        'check_in' => '2026-07-20 10:00:00',
        'total_work_minutes' => 480,
    ]);

    $controller = app(EmployeeController::class);
    $request = Request::create('/employees/'.$employee->id, 'GET', ['month' => '2026-07']);

    $response = $controller->show($request, $employee, app(AttendanceSyncService::class));
    $data = $response->getData();
    $preJoinAttendanceId = $employee->attendances()->whereDate('date', '2026-07-10')->value('id');

    expect($data['startDate'])->toBe('2026-07-15');
    expect($data['stats']['present'])->toBe(1);
    expect($data['stats']['total_hours'])->toBe(8.0);
    expect($data['attendances']->getCollection()->pluck('attendance.id'))->not->toContain($preJoinAttendanceId);
});

it('builds attendance rows from already-pulled raw device data when none exist yet', function () {
    createEmployeeShowSchema();

    $employee = Employee::create(['name' => 'Raw Data Employee', 'device_user_id' => 'DU3']);

    RawDeviceData::create([
        'deviceUserId' => 'DU3',
        'employeeName' => 'Raw Data Employee',
        'date' => '2026-07-15',
        'time' => '10:15:00',
        'recordTime' => '2026-07-15 10:15:00',
        'uniqueKey' => 'DU3|2026-07-15 10:15:00',
    ]);

    $controller = app(EmployeeController::class);
    $request = Request::create('/employees/'.$employee->id, 'GET', ['month' => '2026-07']);

    $response = $controller->show($request, $employee, app(AttendanceSyncService::class));
    $data = $response->getData();

    $matchingRow = $data['attendances']->getCollection()
        ->first(fn ($row) => $row->date->toDateString() === '2026-07-15');

    expect($matchingRow)->not->toBeNull();
    expect($matchingRow->status)->toBe('present');
});

it('classifies days with no punch as weekend (per Attendance Settings) or absent', function () {
    createEmployeeShowSchema();

    // 2026-07-20 is a Monday; configure Friday+Saturday as the weekend so
    // both weekend days and one absent weekday fall inside the range below.
    AttendanceSettings::query()->create([
        'id' => 1,
        'weekend_days' => ['fri', 'sat'],
    ]);

    $employee = Employee::create(['name' => 'Weekend Test Employee', 'device_user_id' => 'DU4']);

    // Only punches in on the Monday - every other day in the range should
    // resolve to either "weekend" or "absent".
    $employee->attendances()->create([
        'date' => '2026-07-20',
        'check_in' => '2026-07-20 10:00:00',
        'total_work_minutes' => 480,
    ]);

    $controller = app(EmployeeController::class);
    $request = Request::create('/employees/'.$employee->id, 'GET', [
        'start_date' => '2026-07-17', // Friday
        'end_date' => '2026-07-21', // Tuesday
    ]);

    $response = $controller->show($request, $employee, app(AttendanceSyncService::class));
    $rows = $response->getData()['attendances']->getCollection()->keyBy(fn ($row) => $row->date->toDateString());

    expect($rows->get('2026-07-17')->status)->toBe('weekend'); // Friday
    expect($rows->get('2026-07-18')->status)->toBe('weekend'); // Saturday
    expect($rows->get('2026-07-19')->status)->toBe('absent'); // Sunday, not a configured weekend day
    expect($rows->get('2026-07-20')->status)->toBe('present'); // Monday, punched in
    expect($rows->get('2026-07-21')->status)->toBe('absent'); // Tuesday, no punch
});

it('loads without a date filter (the default "View" link with no query params)', function () {
    // Regression test: this app configures Date::use(CarbonImmutable::class)
    // globally, so the now() helper returns CarbonImmutable. Visiting the
    // page with no month/start_date/end_date query params exercises the
    // now()-based fallback in resolveAttendanceRange(), which previously
    // leaked a CarbonImmutable into a method strictly typed to Carbon\Carbon
    // and threw a TypeError.
    createEmployeeShowSchema();

    $employee = Employee::create([
        'name' => 'No Filter Employee',
        'device_user_id' => 'DU5',
        'job_join_date' => now()->subYear(),
    ]);

    $controller = app(EmployeeController::class);
    $request = Request::create('/employees/'.$employee->id, 'GET');

    $response = $controller->show($request, $employee, app(AttendanceSyncService::class));

    expect($response->getData()['attendances'])->not->toBeNull();
});
