<?php

use App\Models\Employee;
use App\Models\RawDeviceData;
use App\Models\Shift;
use App\Services\AttendanceSyncService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

function createAttendanceSyncSchema(): void
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
}

it('does not mark a check-in before the shift start time as late', function () {
    createAttendanceSyncSchema();

    $shift = Shift::create([
        'name' => 'Default Shift',
        'start_time' => '10:30:00',
        'end_time' => '18:00:00',
        'is_default' => true,
    ]);

    $employee = Employee::create([
        'name' => 'On Time Employee',
        'device_user_id' => 'DU1',
        'shift_id' => $shift->id,
    ]);

    RawDeviceData::create([
        'deviceUserId' => 'DU1',
        'employeeName' => 'On Time Employee',
        'date' => '2026-07-30',
        'time' => '10:04:00',
        'recordTime' => '2026-07-30 10:04:00',
        'uniqueKey' => 'DU1|2026-07-30 10:04:00',
    ]);

    $result = app(AttendanceSyncService::class)->syncForDate(Carbon::parse('2026-07-30'));

    expect($result['created'])->toBe(1);

    $attendance = $employee->attendances()->first();

    expect($attendance->late_status)->toBeFalse();
});

it('marks a check-in after the shift start time as late', function () {
    createAttendanceSyncSchema();

    $shift = Shift::create([
        'name' => 'Default Shift',
        'start_time' => '10:30:00',
        'end_time' => '18:00:00',
        'is_default' => true,
    ]);

    $employee = Employee::create([
        'name' => 'Late Employee',
        'device_user_id' => 'DU2',
        'shift_id' => $shift->id,
    ]);

    RawDeviceData::create([
        'deviceUserId' => 'DU2',
        'employeeName' => 'Late Employee',
        'date' => '2026-07-30',
        'time' => '11:15:00',
        'recordTime' => '2026-07-30 11:15:00',
        'uniqueKey' => 'DU2|2026-07-30 11:15:00',
    ]);

    $result = app(AttendanceSyncService::class)->syncForDate(Carbon::parse('2026-07-30'));

    expect($result['created'])->toBe(1);

    $attendance = $employee->attendances()->first();

    expect($attendance->late_status)->toBeTrue();
});

it('does not leak an evening check-out into the next day\'s attendance record', function () {
    createAttendanceSyncSchema();

    $shift = Shift::create([
        'name' => 'Default Shift',
        'start_time' => '10:30:00',
        'end_time' => '18:00:00',
        'is_default' => true,
    ]);

    $employee = Employee::create([
        'name' => 'Evening Checkout Employee',
        'device_user_id' => 'DU3',
        'shift_id' => $shift->id,
    ]);

    RawDeviceData::create([
        'deviceUserId' => 'DU3',
        'employeeName' => 'Evening Checkout Employee',
        'date' => '2026-07-29',
        'time' => '10:15:00',
        'recordTime' => '2026-07-29 10:15:00',
        'uniqueKey' => 'DU3|2026-07-29 10:15:00',
    ]);

    RawDeviceData::create([
        'deviceUserId' => 'DU3',
        'employeeName' => 'Evening Checkout Employee',
        'date' => '2026-07-29',
        'time' => '18:10:00',
        'recordTime' => '2026-07-29 18:10:00',
        'uniqueKey' => 'DU3|2026-07-29 18:10:00',
    ]);

    $service = app(AttendanceSyncService::class);
    $service->syncForDate(Carbon::parse('2026-07-29'));
    $service->syncForDate(Carbon::parse('2026-07-30'));

    $day29 = $employee->attendances()->whereDate('date', '2026-07-29')->first();
    $day30 = $employee->attendances()->whereDate('date', '2026-07-30')->first();

    expect($day29)->not->toBeNull();
    expect($day29->check_in_utc->format('H:i'))->toBe('10:15');
    expect($day29->check_out_utc->format('Y-m-d H:i'))->toBe('2026-07-29 18:10');
    expect($day29->early_leave_status)->toBeFalse();

    expect($day30)->toBeNull();
});

it('uses the 1st punch as check-in and 2nd punch as check-out regardless of time of day', function () {
    createAttendanceSyncSchema();

    $shift = Shift::create([
        'name' => 'Default Shift',
        'start_time' => '10:30:00',
        'end_time' => '18:00:00',
        'is_default' => true,
    ]);

    $employee = Employee::create([
        'name' => 'Two Morning Punches Employee',
        'device_user_id' => 'DU4',
        'shift_id' => $shift->id,
    ]);

    // Both punches fall before 13:00 - the old time-window rule would have
    // left check-out empty. The 1st/2nd punch rule should still pair them.
    RawDeviceData::create([
        'deviceUserId' => 'DU4',
        'employeeName' => 'Two Morning Punches Employee',
        'date' => '2026-07-30',
        'time' => '09:05:00',
        'recordTime' => '2026-07-30 09:05:00',
        'uniqueKey' => 'DU4|2026-07-30 09:05:00',
    ]);

    RawDeviceData::create([
        'deviceUserId' => 'DU4',
        'employeeName' => 'Two Morning Punches Employee',
        'date' => '2026-07-30',
        'time' => '11:30:00',
        'recordTime' => '2026-07-30 11:30:00',
        'uniqueKey' => 'DU4|2026-07-30 11:30:00',
    ]);

    // A 3rd punch later the same day must be ignored for check-in/check-out.
    RawDeviceData::create([
        'deviceUserId' => 'DU4',
        'employeeName' => 'Two Morning Punches Employee',
        'date' => '2026-07-30',
        'time' => '17:45:00',
        'recordTime' => '2026-07-30 17:45:00',
        'uniqueKey' => 'DU4|2026-07-30 17:45:00',
    ]);

    app(AttendanceSyncService::class)->syncForDate(Carbon::parse('2026-07-30'));

    $attendance = $employee->attendances()->first();

    expect($attendance->check_in_utc->format('H:i'))->toBe('09:05');
    expect($attendance->check_out_utc->format('H:i'))->toBe('11:30');
});
