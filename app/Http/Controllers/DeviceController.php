<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\APIDeviceCheckOnline;
use App\Services\APIDeviceUserSync;
use HasinHayder\Tyro\Support\TyroAudit;
use Illuminate\Http\Request;
use Throwable;

class DeviceController extends Controller
{
    public function __construct(
        private APIDeviceCheckOnline $apiDeviceCheckOnline,
        private APIDeviceUserSync $apiDeviceUserSync,
    ) {}

    public function index()
    {
        $devices = Device::all();
        $stats = [
            'total' => Device::query()->count('id'),
            'online' => Device::query()->where('status', '=', 'online', 'and')->count('id'),
            'offline' => Device::query()->where('status', '=', 'offline', 'and')->count('id'),
            'error' => Device::query()->where('status', '=', 'error', 'and')->count('id'),
        ];
        $deviceTypes = ['fingerprint', 'face_recognition'];

        return view('dashboard.devices', compact('devices', 'stats', 'deviceTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'device_type' => 'required|in:fingerprint,face_recognition',
            'connection_type' => 'required|in:api,adms',
            'ip_address' => 'nullable|ip|required_if:connection_type,adms',
            'api_endpoint' => 'nullable|ip|required_if:connection_type,api',
            'api_url' => 'nullable|string|required_if:connection_type,api|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'serial_number' => 'required|string|unique:devices,serial_number',
            'location' => 'nullable|string|max:255',
            'sync_interval_minutes' => 'required|integer|min:1',
            'auto_sync' => 'boolean',
        ]);

        $device = Device::create($validated);

        $connectionResult = $this->syncConnectionStatus($device);

        $newValues = $this->deviceAuditValues($device->fresh());
        $newValues['connection_test'] = $connectionResult;

        $this->auditSafely('device.created', $device, null, $newValues);

        $message = $connectionResult['ok']
            ? 'Device added successfully. Device is active and online.'
            : 'Device added, but connection test failed: '.$connectionResult['message'];

        return back()->with($connectionResult['ok'] ? 'success' : 'error', $message);
    }

    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'device_type' => 'required|in:fingerprint,face_recognition',
            'connection_type' => 'required|in:api,adms',
            'ip_address' => 'nullable|ip|required_if:connection_type,adms',
            'api_endpoint' => 'nullable|ip|required_if:connection_type,api',
            'api_url' => 'nullable|string|required_if:connection_type,api|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'serial_number' => 'required|string|unique:devices,serial_number,'.$device->id,
            'location' => 'nullable|string|max:255',
            'sync_interval_minutes' => 'required|integer|min:1',
            'auto_sync' => 'boolean',
        ]);

        $oldValues = $this->deviceAuditValues($device);

        $device->update($validated);
        $connectionResult = $this->syncConnectionStatus($device);

        $newValues = $this->deviceAuditValues($device->fresh());
        $newValues['changed_fields'] = array_keys(array_diff_assoc($newValues, $oldValues));
        $newValues['connection_test'] = $connectionResult;

        $this->auditSafely('device.updated', $device, $oldValues, $newValues);

        $message = $connectionResult['ok']
            ? 'Device updated successfully. Device is active and online.'
            : 'Device updated, but connection test failed: '.$connectionResult['message'];

        return back()->with($connectionResult['ok'] ? 'success' : 'error', $message);
    }

    public function test(Device $device)
    {
        $connectionResult = $this->syncConnectionStatus($device);

        $this->auditSafely('device.tested', $device, null, [
            'connection_test' => $connectionResult,
        ]);

        $message = $connectionResult['ok']
            ? 'Device test successful. Device is active and online.'
            : 'Device test failed: '.$connectionResult['message'];

        return back()->with($connectionResult['ok'] ? 'success' : 'error', $message);
    }

    public function syncUsers(Device $device)
    {
        $result = $this->apiDeviceUserSync->sync($device);

        if ($result['ok']) {
            $device->forceFill([
                'status' => 'online',
                'last_sync_at' => now(),
                'last_error' => null,
            ])->save();

            $this->auditSafely('device.users_synced', $device, null, [
                'sync_result' => $result,
            ]);

            $message = sprintf(
                'Device users synced successfully. Total: %d, Created: %d, Updated: %d, Skipped: %d.',
                $result['total'],
                $result['created'],
                $result['updated'],
                $result['skipped'],
            );

            return back()->with('success', $message);
        }

        $device->forceFill([
            'status' => 'error',
            'last_error' => $result['message'],
        ])->save();

        $this->auditSafely('device.users_sync_failed', $device, null, [
            'sync_result' => $result,
        ]);

        return back()->with('error', 'Device user sync failed: '.$result['message']);
    }

    public function destroy(Device $device)
    {
        $oldValues = $this->deviceAuditValues($device);

        Device::destroy($device->getKey());

        $this->auditSafely('device.deleted', $device, $oldValues, null);

        return back()->with('success', 'Device deleted successfully.');
    }

    protected function syncConnectionStatus(Device $device): array
    {
        $result = $this->apiDeviceCheckOnline->check($device);

        if ($result['ok']) {
            $device->forceFill([
                'status' => 'online',
                'last_sync_at' => now(),
                'last_error' => null,
            ])->save();

            return $result;
        }

        $device->forceFill([
            'status' => 'error',
            'last_error' => $result['message'],
        ])->save();

        return $result;
    }

    protected function auditSafely(string $event, ?Device $auditable = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        try {
            TyroAudit::log($event, $auditable, $oldValues, $newValues);
        } catch (Throwable $e) {
            // Keep device operations functional even if audit persistence fails.
        }
    }

    protected function deviceAuditValues(?Device $device): array
    {
        if (! $device) {
            return [];
        }

        return [
            'id' => $device->id,
            'name' => $device->name,
            'device_type' => $device->device_type,
            'connection_type' => $device->connection_type,
            'ip_address' => $device->ip_address,
            'api_endpoint' => $device->api_endpoint,
            'api_url' => $device->api_url,
            'port' => $device->port,
            'serial_number' => $device->serial_number,
            'location' => $device->location,
            'sync_interval_minutes' => $device->sync_interval_minutes,
            'auto_sync' => $device->auto_sync,
            'status' => $device->status,
            'last_sync_at' => optional($device->last_sync_at)?->toDateTimeString(),
            'last_error' => $device->last_error,
        ];
    }
}
