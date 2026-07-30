<?php

use App\Http\Controllers\AttendancesController;
use App\Models\Attendance;
use App\Services\SmartAttendanceSyncService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

function createAttendancesTable(): void
{
    Schema::create('attendances', function (Blueprint $table) {
        $table->id();
        $table->string('employee_name')->nullable();
        $table->string('device_user_id')->nullable();
        $table->date('date')->nullable();
        $table->dateTime('check_in')->nullable();
        $table->dateTime('check_out')->nullable();
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

it('triggers a smart sync when the selected date has no attendance rows', function () {
    createAttendancesTable();

    $syncService = Mockery::mock(SmartAttendanceSyncService::class);
    $syncService->shouldReceive('sync')
        ->once()
        ->withArgs(fn (Carbon $date) => $date->toDateString() === '2026-07-30')
        ->andReturn([
            'date' => '2026-07-30',
            'created' => 1,
            'updated' => 0,
            'unchanged' => 0,
            'total_groups' => 1,
        ]);

    $controller = app(AttendancesController::class);
    $request = new Request(['date' => '2026-07-30']);

    $response = $controller->index($request, $syncService);

    expect($response->getData()['syncResult']['created'])->toBe(1);
    expect($response->getData()['date'])->toBe('2026-07-30');
});

it('returns a rendered panel as json for ajax requests instead of reloading the page', function () {
    createAttendancesTable();

    $syncService = Mockery::mock(SmartAttendanceSyncService::class);
    $syncService->shouldReceive('sync')
        ->once()
        ->andReturn([
            'ok' => true,
            'date' => '2026-07-30',
            'created' => 1,
            'updated' => 0,
            'unchanged' => 0,
            'total_groups' => 1,
            'message' => 'Data updated: 1 created, 0 updated, 0 unchanged from 1 employee records.',
        ]);

    $controller = app(AttendancesController::class);
    $request = Request::create('/attendances', 'GET', ['date' => '2026-07-30']);
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');

    $response = $controller->index($request, $syncService);
    $payload = json_decode($response->getContent(), true);

    expect($response->headers->get('Content-Type'))->toContain('application/json');
    expect($payload['date'])->toBe('2026-07-30');
    expect($payload['syncResult']['message'])->toBe('Data updated: 1 created, 0 updated, 0 unchanged from 1 employee records.');
    expect($payload['html'])->toContain('Daily Punches');
});

it('reports the date it checked even when no raw device data exists for it', function () {
    Schema::create('devices', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('ip_address')->nullable();
        $table->string('api_endpoint')->nullable();
        $table->string('api_url')->nullable();
        $table->integer('port')->nullable();
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

    createAttendancesTable();

    $service = app(SmartAttendanceSyncService::class);

    $result = $service->sync(Carbon::parse('2026-07-26'));

    expect($result['date'])->toBe('2026-07-26');
    expect($result['ok'])->toBeTrue();
    expect($result['total_groups'])->toBe(0);
});

it('allows an admin to quick-update check-in, check-out, status, and remarks', function () {
    createAttendancesTable();

    $attendanceId = DB::table('attendances')->insertGetId([
        'employee_name' => 'Jane Doe',
        'date' => '2026-07-30',
        'check_in' => '2026-07-30 11:00:00',
        'check_out' => '2026-07-30 17:00:00',
        'total_work_minutes' => 360,
        'late_status' => true,
        'early_leave_status' => true,
        'remarks' => 'Late by 30 minutes; Early leave by 1 hour',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $admin = new class
    {
        public function hasAnyRole(array $roles): bool
        {
            return true;
        }
    };

    $request = Request::create("/attendances/{$attendanceId}/quick-update", 'PATCH', [
        'check_in' => '09:00',
        'check_out' => '18:00',
        'status' => 'on_time',
        'remarks' => 'Adjusted manually',
    ]);
    $request->setUserResolver(fn () => $admin);

    $controller = app(AttendancesController::class);
    $attendance = Attendance::findOrFail($attendanceId);

    $response = $controller->quickUpdate($request, $attendance);
    $payload = json_decode($response->getContent(), true);

    expect($payload['ok'])->toBeTrue();

    $attendance->refresh();
    expect($attendance->late_status)->toBeFalse();
    expect($attendance->early_leave_status)->toBeFalse();
    expect($attendance->remarks)->toBe('Adjusted manually');
    expect($attendance->total_work_minutes)->toBe(540);
});

it('blocks quick-update for non-admin users', function () {
    createAttendancesTable();

    $attendanceId = DB::table('attendances')->insertGetId([
        'employee_name' => 'Jane Doe',
        'date' => '2026-07-30',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $nonAdmin = new class
    {
        public function hasAnyRole(array $roles): bool
        {
            return false;
        }
    };

    $request = Request::create("/attendances/{$attendanceId}/quick-update", 'PATCH', [
        'status' => 'on_time',
    ]);
    $request->setUserResolver(fn () => $nonAdmin);

    $controller = app(AttendancesController::class);
    $attendance = Attendance::findOrFail($attendanceId);

    expect(fn () => $controller->quickUpdate($request, $attendance))
        ->toThrow(HttpException::class);
});
