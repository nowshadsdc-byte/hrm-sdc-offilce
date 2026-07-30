<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSettings;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AttendanceSettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        $settings = AttendanceSettings::current();

        return view('dashboard.attendance-settings', [
            'settings' => [
                'working_hours_start' => optional($settings->working_hours_start)?->format('H:i') ?? '09:00',
                'working_hours_end' => optional($settings->working_hours_end)?->format('H:i') ?? '18:00',
                'weekend_days' => $settings->weekend_days ?? ['fri'],
                'timezone' => $settings->timezone ?? 'Asia/Dhaka',
                'auto_backup_enabled' => (bool) ($settings->auto_backup_enabled ?? false),
                'backup_frequency' => $settings->backup_frequency ?? 'weekly',
                'backup_path' => $settings->backup_path ?? 'storage/app/backups',
                'last_backup_at' => optional($settings->last_backup_at)?->toDateTimeString(),
                'default_annual_leave_days' => $settings->default_annual_leave_days ?? 20,
            ],
            'shifts' => Shift::all(),
        ]);
    }

    /**
     * Update the organization-wide default annual leave allocation.
     * Employees without a personal override use this number.
     */
    public function updateLeavePolicy(Request $request)
    {
        $validated = $request->validate([
            'default_annual_leave_days' => ['required', 'integer', 'min:0', 'max:365'],
        ]);

        $settings = AttendanceSettings::current();
        $settings->update($validated);

        return back()->with('success', 'Leave policy updated successfully.');
    }

    /**
     * Update working hours, weekend, timezone settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'working_hours_start' => ['required', 'date_format:H:i'],
            'working_hours_end' => ['required', 'date_format:H:i', 'after:working_hours_start'],
            'weekend_days' => ['required', 'array', 'min:1'],
            'weekend_days.*' => ['in:sat,sun,mon,tue,wed,thu,fri'],
            'timezone' => ['required', 'timezone'],
            'auto_backup_enabled' => ['required', 'boolean'],
            'backup_frequency' => ['nullable', 'in:daily,weekly,monthly'],
            'backup_path' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $validated['auto_backup_enabled']) {
            $validated['backup_frequency'] = null;
        }

        $settings = AttendanceSettings::current();
        $settings->update($validated);

        return back()->with('success', 'Attendance settings updated successfully.');
    }

    /**
     * Update backup configuration.
     */
    public function updateBackupSettings(Request $request)
    {
        $validated = $request->validate([
            'auto_backup_enabled' => ['required', 'boolean'],
            'backup_frequency' => ['nullable', 'in:daily,weekly,monthly'],
            'backup_path' => ['nullable', 'string'],
        ]);

        $settings = AttendanceSettings::current();
        $settings->update($validated);

        return back()->with('success', 'Backup settings updated successfully.');
    }

    /**
     * Trigger a manual backup.
     */
    public function backupNow()
    {
        $settings = AttendanceSettings::current();

        // Example: run a custom backup artisan command, e.g. spatie/laravel-backup
        Artisan::call('backup:run');

        $settings->update(['last_backup_at' => now()]);

        return back()->with('success', 'Backup completed successfully.');
    }

    /**
     * Restore from an uploaded backup file.
     */
    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:zip,sql'],
        ]);

        $file = $request->file('backup_file');
        $path = $file->storeAs('backups/restore', $file->getClientOriginalName());

        // TODO: Implement actual restore logic here
        // e.g. dispatch a job to import the SQL dump or extract the zip

        return back()->with('success', 'Backup file uploaded. Restore process started.');
    }

    /**
     * Store a new shift.
     */
    public function storeShift(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        Shift::create($validated);

        return back()->with('success', 'Shift created successfully.');
    }

    /**
     * Update an existing shift.
     */
    public function updateShift(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $shift->update($validated);

        return back()->with('success', 'Shift updated successfully.');
    }

    /**
     * Delete a shift.
     */
    public function destroyShift(Shift $shift)
    {
        $shift->delete();

        return back()->with('success', 'Shift deleted successfully.');
    }
}
