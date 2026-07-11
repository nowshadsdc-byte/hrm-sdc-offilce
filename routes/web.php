<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendancesController;
use App\Http\Controllers\AttendanceSettingsController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\SyncTodayController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::resource('employees', EmployeeController::class);

Route::prefix('attendance-settings')->middleware(['auth', 'attendance-settings.access'])->group(function () {
    Route::get('/', [AttendanceSettingsController::class, 'index'])->name('attendance-settings.index');
    Route::put('/', [AttendanceSettingsController::class, 'update'])->name('attendance-settings.update');
    Route::put('/backup-config', [AttendanceSettingsController::class, 'updateBackupSettings'])->name('attendance-settings.backup-config');
    Route::post('/backup-now', [AttendanceSettingsController::class, 'backupNow'])->name('attendance-settings.backup-now');
    Route::post('/restore', [AttendanceSettingsController::class, 'restore'])->name('attendance-settings.restore');
    Route::post('/shifts', [AttendanceSettingsController::class, 'storeShift'])->name('shifts.store');
    Route::put('/shifts/{shift}', [AttendanceSettingsController::class, 'updateShift'])->name('shifts.update');
    Route::delete('/shifts/{shift}', [AttendanceSettingsController::class, 'destroyShift'])->name('shifts.destroy');
});

Route::prefix('holiday-calendar')->middleware(['auth', 'holiday-calendar.access'])->group(function () {
    Route::get('/', [HolidayController::class, 'index'])->name('holiday-calendar.index');
    Route::post('/', [HolidayController::class, 'store'])->name('holiday-calendar.store');
    Route::delete('/{holiday}', [HolidayController::class, 'destroy'])->name('holiday-calendar.destroy');
});

Route::prefix('leave-requests')->middleware(['auth'])->group(function () {
    Route::get('/', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::post('/', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::put('/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::put('/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
    Route::delete('/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->name('leave-requests.destroy');
});

Route::prefix('attendances')->middleware(['auth', 'tyro-dashboard.admin'])->group(function () {
    Route::get('/', [AttendancesController::class, 'index'])->name('attendances.index');
    Route::get('/create', [AttendancesController::class, 'create'])->name('attendances.create');
    Route::post('/', [AttendancesController::class, 'store'])->name('attendances.store');
    Route::get('/export', [AttendancesController::class, 'export'])->name('attendances.export');
    Route::get('/{attendance}', [AttendancesController::class, 'show'])->name('attendances.show');
    Route::get('/{attendance}/edit', [AttendancesController::class, 'edit'])->name('attendances.edit');
    Route::put('/{attendance}', [AttendancesController::class, 'update'])->name('attendances.update');
    Route::delete('/{attendance}', [AttendancesController::class, 'destroy'])->name('attendances.destroy');
});

Route::get('dashboard/devices', [DeviceController::class, 'index'])->middleware(['auth', 'tyro-dashboard.admin'])->name('dashboard.devices');
Route::post('dashboard/devices', [DeviceController::class, 'store'])->middleware(['auth', 'tyro-dashboard.admin'])->name('devices.store');
Route::put('dashboard/devices/{device}', [DeviceController::class, 'update'])->middleware(['auth', 'tyro-dashboard.admin'])->name('devices.update');
Route::post('dashboard/devices/{device}/test', [DeviceController::class, 'test'])->middleware(['auth', 'tyro-dashboard.admin'])->name('devices.test');
Route::post('dashboard/devices/{device}/sync-users', [DeviceController::class, 'syncUsers'])->middleware(['auth', 'tyro-dashboard.admin'])->name('devices.sync-users');
Route::post('dashboard/devices/{device}/import-attendance', [AttendanceController::class, 'syncDeviceData'])->middleware(['auth', 'tyro-dashboard.admin'])->name('devices.import-attendance');
Route::post('dashboard/devices/{device}/sync-today', [SyncTodayController::class, 'sync'])->middleware(['auth', 'tyro-dashboard.admin'])->name('devices.sync-today');
Route::delete('dashboard/devices/{device}', [DeviceController::class, 'destroy'])->middleware(['auth', 'tyro-dashboard.admin'])->name('devices.destroy');

Route::get('dashboard/attendance', [AttendancesController::class, 'index'])->middleware(['auth', 'tyro-dashboard.admin'])->name('dashboard.attendance');
Route::post('dashboard/attendance/sync', [AttendancesController::class, 'sync'])->middleware(['auth', 'tyro-dashboard.admin'])->name('dashboard.attendance.sync');
Route::post('dashboard/attendance/sync-now', [AttendancesController::class, 'syncNow'])->middleware(['auth', 'tyro-dashboard.admin'])->name('dashboard.attendance.sync-now');
Route::put('dashboard/attendance/{attendance}/adjust', [AttendancesController::class, 'adjust'])->middleware(['auth', 'tyro-dashboard.admin'])->name('dashboard.attendance.adjust');
