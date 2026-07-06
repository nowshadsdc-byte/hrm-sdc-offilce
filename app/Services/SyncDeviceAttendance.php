<?php

namespace App\Services;

use App\Models\Device;
use App\Models\RawDeviceData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SyncDeviceAttendance
{
    /**
     * Sync device attendance records and report progress via callback.
     *
     * @param  callable(array{total:int,processed:int,inserted:int,skipped:int,failed:int,percentage:float,message:string}):void|null  $onProgress
     * @return array{ok:bool,total:int,processed:int,inserted:int,skipped:int,failed:int,message:string}
     */
    public function sync(Device $device, ?callable $onProgress = null): array
    {
        $endpoint = $this->resolveAttendanceEndpoint($device);
        $targetIp = (string) ($device->api_endpoint ?: $device->ip_address);
        $targetPort = (int) ($device->port ?: 4370);

        if ($targetIp === '') {
            return [
                'ok' => false,
                'total' => 0,
                'processed' => 0,
                'inserted' => 0,
                'skipped' => 0,
                'failed' => 0,
                'message' => 'Device IP is missing.',
            ];
        }

        $response = Http::acceptJson()
            ->connectTimeout(10)
            ->timeout(120)
            ->retry(2, 300)
            ->get($endpoint, [
                'ip' => $targetIp,
                'port' => $targetPort,
            ]);

        if (! $response->successful()) {
            return [
                'ok' => false,
                'total' => 0,
                'processed' => 0,
                'inserted' => 0,
                'skipped' => 0,
                'failed' => 0,
                'message' => 'Attendance API request failed with status '.$response->status().'.',
            ];
        }

        $payload = $response->json();
        $records = $this->extractRecords(is_array($payload) ? $payload : []);

        if ($records === null) {
            return [
                'ok' => false,
                'total' => 0,
                'processed' => 0,
                'inserted' => 0,
                'skipped' => 0,
                'failed' => 0,
                'message' => 'Invalid API payload format for attendance data.',
            ];
        }

        $total = count($records);
        $processed = 0;
        $inserted = 0;
        $skipped = 0;
        $failed = 0;

        if ($onProgress !== null) {
            $onProgress([
                'total' => $total,
                'processed' => 0,
                'inserted' => 0,
                'skipped' => 0,
                'failed' => 0,
                'percentage' => 0,
                'message' => 'Total Records: '.number_format($total),
            ]);
        }

        foreach (array_chunk($records, 500) as $chunkIndex => $chunk) {
            $rawRows = [];

            foreach ($chunk as $record) {
                try {
                    if (! is_array($record)) {
                        $failed++;
                        $processed++;

                        continue;
                    }

                    $deviceUserId = $this->normalizeScalar($record['deviceUserId'] ?? $record['device_user_id'] ?? null);
                    $employeeName = $this->normalizeScalar($record['employeeName'] ?? $record['employee_name'] ?? null);
                    $recordTimeRaw = $this->normalizeScalar($record['recordTime'] ?? $record['record_time'] ?? null);

                    if ($deviceUserId === null || $employeeName === null || $recordTimeRaw === null) {
                        $failed++;
                        $processed++;

                        continue;
                    }

                    $recordTime = Carbon::parse($recordTimeRaw);
                    $recordTimestamp = $recordTime->format('Y-m-d H:i:s');
                    $rawUniqueKey = $deviceUserId.'|'.$recordTimestamp;

                    $rawRows[] = [
                        'deviceUserId' => $deviceUserId,
                        'employeeName' => $employeeName,
                        'date' => $recordTime->format('Y-m-d'),
                        'time' => $recordTime->format('H:i:s'),
                        'recordTime' => $recordTimestamp,
                        'uniqueKey' => $rawUniqueKey,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                } catch (Throwable $e) {
                    $failed++;
                    $processed++;

                    Log::warning('Attendance import record parse failed.', [
                        'device_id' => $device->id,
                        'record' => $record,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($rawRows !== []) {
                $insertedInChunk = RawDeviceData::query()->insertOrIgnore($rawRows);
                $inserted += $insertedInChunk;
                $skipped += count($rawRows) - $insertedInChunk;
                $processed += count($rawRows);
            }

            $percentage = $total > 0 ? round(($processed / $total) * 100, 2) : 100;

            if ($onProgress !== null) {
                $onProgress([
                    'total' => $total,
                    'processed' => min($processed, $total),
                    'inserted' => $inserted,
                    'skipped' => $skipped,
                    'failed' => $failed,
                    'percentage' => $percentage,
                    'message' => sprintf('Processing %s of %s', number_format(min($processed, $total)), number_format($total)),
                ]);
            }

            // Soft yield between chunks for large imports.
            if ($chunkIndex % 5 === 0) {
                usleep(30000);
            }
        }

        return [
            'ok' => true,
            'total' => $total,
            'processed' => min($processed, $total),
            'inserted' => $inserted,
            'skipped' => $skipped,
            'failed' => $failed,
            'message' => 'Import completed successfully.',
        ];
    }

    protected function resolveAttendanceEndpoint(Device $device): string
    {
        $baseUrl = $this->normalizeBaseUrl((string) ($device->api_url ?: config('services.device_gateway.url', 'http://localhost:4370')));
        $path = (string) parse_url($baseUrl, PHP_URL_PATH);

        if (Str::contains(Str::lower($path), '/api/attendance')) {
            return rtrim($baseUrl, '/');
        }

        return rtrim($baseUrl, '/').'/api/attendance';
    }

    protected function normalizeBaseUrl(string $url): string
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            return 'http://localhost:4370';
        }

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
}
