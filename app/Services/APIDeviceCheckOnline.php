<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class APIDeviceCheckOnline
{
    /**
     * Check device connectivity against the local iclock gateway endpoint.
     *
     * @return array{ok: bool, message: string, request_url: string|null, status_code: int|null, response_body: string|null}
     */
    public function check(Device $device): array
    {
        $serviceBaseUrl = $this->normalizeBaseUrl((string) ($device->api_url ?: config('services.device_gateway.url', 'http://localhost:4370')));
        $targetIp = (string) ($device->api_endpoint ?: $device->ip_address);
        $targetPort = (int) ($device->port ?: 4370);

        if ($targetIp === '') {
            return [
                'ok' => false,
                'message' => 'Device IP is missing.',
                'request_url' => null,
                'status_code' => null,
                'response_body' => null,
            ];
        }

        $requestUrl = rtrim($serviceBaseUrl, '/').'/iclock/getrequest';

        try {
            $response = Http::connectTimeout(5)
                ->timeout(10)
                ->retry(2, 200)
                ->get($requestUrl, [
                    'ip' => $targetIp,
                    'port' => $targetPort,
                ]);

            $responseBody = trim((string) $response->body());
            $isOk = $response->successful() && Str::contains(Str::upper($responseBody), 'OK');

            return [
                'ok' => $isOk,
                'message' => $isOk ? 'Device responded OK.' : 'Device did not return OK.',
                'request_url' => $requestUrl.'?ip='.$targetIp.'&port='.$targetPort,
                'status_code' => $response->status(),
                'response_body' => $responseBody,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'request_url' => $requestUrl.'?ip='.$targetIp.'&port='.$targetPort,
                'status_code' => null,
                'response_body' => null,
            ];
        }
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
}
