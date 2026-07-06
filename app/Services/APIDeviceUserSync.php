<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Employee;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class APIDeviceUserSync
{
    /**
     * Fetch users from a biometric device API and synchronize into employees.
     *
     * @return array{ok: bool, message: string, request_url: string|null, status_code: int|null, total: int, created: int, updated: int, skipped: int, errors: int}
     */
    public function sync(Device $device): array
    {
        $requestUrl = $this->resolveUsersEndpoint($device);
        $targetIp = (string) ($device->api_endpoint ?: $device->ip_address);
        $targetPort = (int) ($device->port ?: 4370);

        if ($targetIp === '') {
            return [
                'ok' => false,
                'message' => 'Device IP is missing.',
                'request_url' => null,
                'status_code' => null,
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->retry(2, 250)
                ->get($requestUrl, [
                    'ip' => $targetIp,
                    'port' => $targetPort,
                ]);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'message' => 'Device user API returned an unsuccessful response.',
                    'request_url' => $requestUrl.'?ip='.$targetIp.'&port='.$targetPort,
                    'status_code' => $response->status(),
                    'total' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => 1,
                ];
            }

            $payload = $response->json();
            $users = $this->extractUsers($payload);

            if (! is_array($users)) {
                return [
                    'ok' => false,
                    'message' => 'Invalid response format from device user API.',
                    'request_url' => $requestUrl.'?ip='.$targetIp.'&port='.$targetPort,
                    'status_code' => $response->status(),
                    'total' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => 1,
                ];
            }

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = 0;

            foreach ($users as $record) {
                try {
                    if (! is_array($record)) {
                        $skipped++;

                        continue;
                    }

                    $deviceUserId = $this->normalizeScalar($this->firstValue($record, [
                        'device_user_id',
                        'deviceUserId',
                        'uid',
                        'userId',
                        'userid',
                        'user_id',
                        'id',
                        'pin',
                        'enrollid',
                    ]));
                    $deviceCardNo = $this->normalizeScalar($this->firstValue($record, [
                        'device_cardno',
                        'cardno',
                        'card_no',
                        'card',
                        'badge_no',
                    ]));
                    $name = $this->normalizeScalar($this->firstValue($record, [
                        'name',
                        'username',
                        'userName',
                        'full_name',
                        'fullName',
                        'employee_name',
                        'employeeName',
                    ]));
                    $role = $this->normalizeScalar($this->firstValue($record, [
                        'role',
                        'user_role',
                        'userRole',
                    ]));

                    if ($deviceUserId === null && $deviceCardNo === null) {
                        $skipped++;

                        continue;
                    }

                    $employee = Employee::query()
                        ->when($deviceUserId !== null || $deviceCardNo !== null, function ($query) use ($deviceUserId, $deviceCardNo) {
                            $query->where(function ($nested) use ($deviceUserId, $deviceCardNo) {
                                if ($deviceUserId !== null) {
                                    $nested->orWhere('device_user_id', $deviceUserId);
                                }

                                if ($deviceCardNo !== null) {
                                    $nested->orWhere('device_cardno', $deviceCardNo);
                                }
                            });
                        })
                        ->first();

                    $attributes = [];

                    if ($name !== null) {
                        $attributes['name'] = $name;
                    }

                    if ($deviceUserId !== null) {
                        $attributes['device_user_id'] = $deviceUserId;
                    }

                    if ($deviceCardNo !== null) {
                        $attributes['device_cardno'] = $deviceCardNo;
                    }

                    if ($role !== null) {
                        $attributes['role'] = $role;
                    }

                    if (! $employee) {
                        $employee = Employee::create($this->buildCreatePayload(
                            $name,
                            $deviceUserId,
                            $deviceCardNo,
                            $role,
                        ));

                        $created++;

                        continue;
                    }

                    if ($attributes !== []) {
                        $employee->fill($attributes);

                        if ($employee->isDirty()) {
                            $employee->save();
                            $updated++;
                        }
                    }
                } catch (Throwable $e) {
                    $errors++;

                    Log::error('Device user sync record failed.', [
                        'device_id' => $device->id,
                        'record' => $record,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $total = count($users);

            return [
                'ok' => $errors === 0,
                'message' => $errors === 0
                    ? 'Users synchronized successfully.'
                    : 'Users synchronized with some record errors.',
                'request_url' => $requestUrl.'?ip='.$targetIp.'&port='.$targetPort,
                'status_code' => $response->status(),
                'total' => $total,
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
            ];
        } catch (Throwable $e) {
            Log::error('Device user sync request failed.', [
                'device_id' => $device->id,
                'request_url' => $requestUrl.'?ip='.$targetIp.'&port='.$targetPort,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'request_url' => $requestUrl.'?ip='.$targetIp.'&port='.$targetPort,
                'status_code' => null,
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 1,
            ];
        }
    }

    protected function resolveUsersEndpoint(Device $device): string
    {
        $baseUrl = $this->normalizeBaseUrl((string) ($device->api_url ?: config('services.device_gateway.url', 'http://localhost:4370')));
        $path = (string) parse_url($baseUrl, PHP_URL_PATH);

        if (Str::contains(Str::lower($path), '/api/users')) {
            return rtrim($baseUrl, '/');
        }

        return rtrim($baseUrl, '/').'/api/users';
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
    protected function extractUsers(array $payload): ?array
    {
        if ($this->isListArray($payload)) {
            return $payload;
        }

        foreach (['data', 'users', 'result'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $payload[$key];
            }
        }

        return null;
    }

    protected function isListArray(array $value): bool
    {
        return array_is_list($value);
    }

    /**
     * @param  array<mixed>  $record
     * @param  array<int, string>  $keys
     */
    protected function firstValue(array $record, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $record)) {
                return $record[$key];
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
            $stringValue = trim((string) $value);

            return $stringValue === '' ? null : $stringValue;
        }

        return null;
    }

    protected function buildCreatePayload(?string $name, ?string $deviceUserId, ?string $deviceCardNo, ?string $role): array
    {
        $identity = $deviceUserId ?? $deviceCardNo ?? (string) Str::ulid();

        return [
            'name' => $name ?? 'Device User '.$identity,
            'status' => 'active',
            'clearance_completed' => false,
            'device_user_id' => $deviceUserId,
            'device_cardno' => $deviceCardNo,
            // Demo/default values for fields often needed in HR views/forms.
            'phone' => '01700000000',
            'address' => 'Synced from device API',
            'department' => 'General',
            'designation' => 'Employee',
            'job_title' => 'Employee',
            'job_description' => 'Auto-created from biometric device sync.',
            'nid' => 'NID-'.$identity,
            'role' => $role ?? 'employee',
        ];
    }
}
