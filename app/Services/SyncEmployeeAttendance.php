<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Employee;
use App\Models\RawDeviceData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SyncEmployeeAttendance
{
    /**
     * Pull attendance punches for a single employee from a device's per-user endpoint.
     *
     * @return array{ok:bool,inserted:int,message:string}
     */
    public function sync(Device $device, Employee $employee): array
    {
        $deviceUserId = trim((string) $employee->device_user_id);

        if ($deviceUserId === '') {
            return [
                'ok' => false,
                'inserted' => 0,
                'message' => 'Employee has no Device User ID mapped.',
            ];
        }

        $apiUrl = trim((string) $device->api_url);

        if ($apiUrl === '') {
            return [
                'ok' => false,
                'inserted' => 0,
                'message' => "Device \"{$device->name}\" has no API URL configured.",
            ];
        }

        $targetIp = (string) ($device->api_endpoint ?: $device->ip_address);
        $targetPort = (int) ($device->port ?: 4370);

        if ($targetIp === '') {
            return [
                'ok' => false,
                'inserted' => 0,
                'message' => "Device \"{$device->name}\" has no API Endpoint IP configured.",
            ];
        }

        $endpoint = $this->resolveUserEndpoint($apiUrl, $deviceUserId);

        try {
            $response = Http::acceptJson()
                ->connectTimeout(10)
                ->timeout(60)
                ->retry(2, 300)
                ->get($endpoint, [
                    'ip' => $targetIp,
                    'port' => $targetPort,
                ]);
        } catch (Throwable $e) {
            Log::warning('Employee attendance device pull could not connect.', [
                'device_id' => $device->id,
                'employee_id' => $employee->id,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'inserted' => 0,
                'message' => "Could not reach device \"{$device->name}\" at its configured API URL.",
            ];
        }

        if (! $response->successful()) {
            return [
                'ok' => false,
                'inserted' => 0,
                'message' => "Device \"{$device->name}\" API request failed with status ".$response->status().'.',
            ];
        }

        $payload = $response->json();
        $records = $this->extractRecords(is_array($payload) ? $payload : []);

        if ($records === null) {
            return [
                'ok' => false,
                'inserted' => 0,
                'message' => 'Invalid API payload format for attendance data.',
            ];
        }

        $rawRows = [];

        foreach ($records as $record) {
            try {
                if (! is_array($record)) {
                    continue;
                }

                $recordDeviceUserId = $this->normalizeScalar($record['deviceUserId'] ?? $record['device_user_id'] ?? null);
                $employeeName = $this->normalizeScalar($record['employeeName'] ?? $record['employee_name'] ?? null) ?? $employee->name;
                $recordTimeRaw = $this->normalizeScalar($record['recordTime'] ?? $record['record_time'] ?? null);

                if ($recordTimeRaw === null) {
                    continue;
                }

                // The endpoint is already scoped to this employee, but guard
                // against a device returning extra records for other users.
                if ($recordDeviceUserId !== null && $recordDeviceUserId !== $deviceUserId) {
                    continue;
                }

                $recordTime = $this->parseRecordTime($recordTimeRaw);
                $recordTimezone = $this->resolveRecordTimezone($recordTimeRaw, $recordTime);
                $recordTimestamp = $recordTime->copy()->setTimezone('UTC')->format('Y-m-d H:i:s');

                $rawRows[] = [
                    'deviceUserId' => $deviceUserId,
                    'employeeName' => $employeeName,
                    'date' => $recordTime->copy()->setTimezone('UTC')->format('Y-m-d'),
                    'time' => $recordTime->copy()->setTimezone('UTC')->format('H:i:s'),
                    'recordTime' => $recordTimestamp,
                    'timeZone' => $recordTimezone,
                    'uniqueKey' => $deviceUserId.'|'.$recordTimestamp,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } catch (Throwable $e) {
                Log::warning('Employee attendance import record parse failed.', [
                    'device_id' => $device->id,
                    'employee_id' => $employee->id,
                    'record' => $record,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $inserted = 0;

        foreach (array_chunk($rawRows, 500) as $chunk) {
            $inserted += RawDeviceData::query()->insertOrIgnore($chunk);
        }

        return [
            'ok' => true,
            'inserted' => $inserted,
            'message' => sprintf('Pulled %d punch record(s), %d new.', count($rawRows), $inserted),
        ];
    }

    protected function resolveUserEndpoint(string $apiUrl, string $deviceUserId): string
    {
        $baseUrl = $this->normalizeBaseUrl($apiUrl);
        $path = (string) parse_url($baseUrl, PHP_URL_PATH);

        if (Str::contains(Str::lower($path), '/api/attendance')) {
            $baseUrl = preg_replace('#/api/attendance.*#i', '', rtrim($baseUrl, '/'));
        }

        return rtrim($baseUrl, '/').'/api/attendance/user/'.rawurlencode($deviceUserId);
    }

    protected function normalizeBaseUrl(string $url): string
    {
        $trimmed = trim($url);

        if (! preg_match('/^https?:\/\//i', $trimmed)) {
            $trimmed = 'http://'.$trimmed;
        }

        return rtrim($trimmed, '/');
    }

    /**
     * @param  array<mixed>  $payload
     * @return array<mixed>|null
     */
    protected function extractRecords(array $payload): ?array
    {
        if (array_is_list($payload)) {
            return $payload;
        }

        foreach (['data', 'records', 'result'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $payload[$key];
            }
        }

        return null;
    }

    protected function normalizeScalar(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            $normalized = trim((string) $value);

            return $normalized === '' ? null : $normalized;
        }

        return null;
    }

    protected function parseRecordTime(string $recordTimeRaw): Carbon
    {
        $trimmed = trim($recordTimeRaw);
        $hasUtcOrOffset = preg_match('/(?:Z|[+-]\d{2}:\d{2})$/i', $trimmed) === 1;

        if ($hasUtcOrOffset) {
            return Carbon::parse($trimmed);
        }

        return Carbon::parse($trimmed, 'UTC');
    }

    protected function resolveRecordTimezone(string $recordTimeRaw, Carbon $recordTime): string
    {
        $trimmed = trim($recordTimeRaw);

        if (preg_match('/(?:Z|[+-]\d{2}:\d{2})$/i', $trimmed) === 1) {
            return $recordTime->getTimezone()->getName() === 'Z'
                ? 'UTC'
                : $recordTime->getTimezone()->getName();
        }

        return 'UTC';
    }
}
