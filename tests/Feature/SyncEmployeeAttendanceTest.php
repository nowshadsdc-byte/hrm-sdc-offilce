<?php

use App\Models\Device;
use App\Models\Employee;
use App\Services\SyncEmployeeAttendance;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Sleep;

function createSyncEmployeeAttendanceSchema(): void
{
    Schema::create('devices', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('ip_address')->nullable();
        $table->string('api_endpoint')->nullable();
        $table->string('api_url')->nullable();
        $table->integer('port')->nullable();
        $table->timestamps();
    });

    Schema::create('employees', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('device_user_id')->nullable();
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
}

it('does not throw when the device gateway is unreachable, and reports a clear failure', function () {
    createSyncEmployeeAttendanceSchema();
    Sleep::fake();

    Http::fake(function () {
        throw new ConnectionException('Failed to connect to localhost port 4370');
    });

    $device = Device::create([
        'name' => 'Offline Gateway',
        'api_url' => 'http://localhost:4370',
        'api_endpoint' => '192.168.10.201',
        'port' => 4370,
    ]);

    $employee = Employee::create(['name' => 'Test Employee', 'device_user_id' => '22']);

    $result = app(SyncEmployeeAttendance::class)->sync($device, $employee);

    expect($result['ok'])->toBeFalse();
    expect($result['inserted'])->toBe(0);
    expect($result['message'])->toContain('Offline Gateway');
});

it('reports a clear failure instead of guessing localhost when a device has no API URL configured', function () {
    createSyncEmployeeAttendanceSchema();

    $device = Device::create([
        'name' => 'Unconfigured Device',
        'api_url' => null,
        'api_endpoint' => '192.168.10.201',
        'port' => 4370,
    ]);

    $employee = Employee::create(['name' => 'Test Employee', 'device_user_id' => '22']);

    $result = app(SyncEmployeeAttendance::class)->sync($device, $employee);

    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toContain('no API URL configured');
});

it('pulls punches using the device\'s own API URL, API Endpoint IP, and port', function () {
    createSyncEmployeeAttendanceSchema();

    Http::fake([
        'https://device.example.test/api/attendance/user/22*' => Http::response([
            'data' => [
                [
                    'deviceUserId' => '22',
                    'employeeName' => 'Test Employee',
                    'recordTime' => '2026-07-30 10:15:00',
                ],
            ],
        ], 200),
    ]);

    $device = Device::create([
        'name' => 'Real Device',
        'api_url' => 'https://device.example.test',
        'api_endpoint' => '10.0.0.5',
        'port' => 4370,
    ]);

    $employee = Employee::create(['name' => 'Test Employee', 'device_user_id' => '22']);

    $result = app(SyncEmployeeAttendance::class)->sync($device, $employee);

    expect($result['ok'])->toBeTrue();
    expect($result['inserted'])->toBe(1);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://device.example.test/api/attendance/user/22?ip=10.0.0.5&port=4370';
    });
});
