<?php

namespace App\Http\Controllers;

use App\Models\OpenWAWebhookEventRecord;
use App\OpenWAWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OpenWAWebhookController extends Controller
{
    /**
     * Receive a webhook delivery from the OpenWA gateway.
     */
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->hasValidSignature($request)) {
            abort(403, 'Invalid webhook signature.');
        }

        $validated = $request->validate([
            'event' => ['required', 'string'],
            'sessionId' => ['nullable', 'string'],
            'clientId' => ['nullable', 'string'],
            'data' => ['nullable'],
        ]);

        $event = OpenWAWebhookEvent::tryFrom($validated['event']);
        $payload = $request->all();
        $data = is_array($validated['data'] ?? null) ? $validated['data'] : $payload;
        $clientId = $validated['clientId']
            ?? $this->findPayloadValue($payload, ['clientId', 'client_id']);

        OpenWAWebhookEventRecord::create([
            'event' => $validated['event'],
            'session_id' => $validated['sessionId'] ?? null,
            'client_id' => $clientId,
            'event_id' => $this->findPayloadValue($data, ['eventId', 'event_id']),
            'chat_id' => $this->findPayloadValue($data, ['chatId', 'chat_id']),
            'message_id' => $this->findPayloadValue($data, ['messageId', 'message_id', '_serialized']),
            'payload' => $payload,
            'received_at' => now(),
        ]);

        Log::info('OpenWA webhook received', [
            'event' => $validated['event'],
            'recognized' => $event !== null,
            'sessionId' => $validated['sessionId'] ?? null,
            'clientId' => $clientId,
            'data' => $validated['data'] ?? $request->except(['event', 'sessionId']),
        ]);

        return response()->json([
            'received' => true,
            'event' => $validated['event'],
            'clientId' => $clientId,
        ]);
    }

    /**
     * Find a scalar identifier in the nested event payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $keys
     */
    protected function findPayloadValue(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_scalar($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $found = $this->findPayloadValue($value, $keys);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Verify the `X-OpenWA-Signature` HMAC header against the configured
     * webhook secret. Verification is skipped when no secret is configured.
     */
    protected function hasValidSignature(Request $request): bool
    {
        $secret = config('services.openwa.webhook_secret');

        if (! $secret) {
            return true;
        }

        $signature = (string) $request->header('X-OpenWA-Signature');

        if (! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, substr($signature, 7));
    }
}
