<?php

use App\Models\OpenWAWebhookEventRecord;
use App\OpenWAWebhookEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['services.openwa.webhook_secret' => null]);

    if (! Schema::hasTable('openwa_webhook_events')) {
        Schema::create('openwa_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event')->index();
            $table->string('session_id')->nullable()->index();
            $table->string('client_id')->nullable()->index();
            $table->string('event_id')->nullable()->index();
            $table->string('chat_id')->nullable()->index();
            $table->string('message_id')->nullable()->index();
            $table->json('payload');
            $table->timestamp('received_at')->index();
            $table->timestamps();
        });
    }

    if (! Schema::hasColumn('openwa_webhook_events', 'client_id')) {
        Schema::table('openwa_webhook_events', function (Blueprint $table) {
            $table->string('client_id')->nullable()->index()->after('session_id');
        });
    }

    OpenWAWebhookEventRecord::query()->delete();
});

it('accepts a recognized event and returns 200 when no webhook secret is configured', function () {
    Log::spy();

    $response = $this->postJson('/webhook/api', [
        'event' => 'message.received',
        'sessionId' => 'session-123',
        'clientId' => 'client-456',
        'data' => ['body' => 'hi'],
    ]);

    $response->assertOk()->assertJson(['received' => true, 'clientId' => 'client-456']);

    Log::shouldHaveReceived('info')->once()->withArgs(
        fn (string $message, array $context) => $message === 'OpenWA webhook received'
            && $context['event'] === 'message.received'
            && $context['recognized'] === true
    );
});

it('persists the webhook payload and extracts nested message identifiers', function () {
    $response = $this->postJson('/webhook/api', [
        'event' => 'message.received',
        'sessionId' => 'session-123',
        'data' => [
            'client' => ['client_id' => 'client-456'],
            'message' => [
                'id' => ['_serialized' => 'message-456'],
                'chatId' => 'chat-789',
            ],
            'eventId' => 'event-012',
        ],
    ]);

    $response->assertOk();

    $record = OpenWAWebhookEventRecord::query()->sole();

    expect($record->event)->toBe('message.received')
        ->and($record->session_id)->toBe('session-123')
        ->and($record->client_id)->toBe('client-456')
        ->and($record->event_id)->toBe('event-012')
        ->and($record->chat_id)->toBe('chat-789')
        ->and($record->message_id)->toBe('message-456')
        ->and($record->payload['data']['message']['chatId'])->toBe('chat-789');
});

it('rejects an unsigned request when a webhook secret is configured', function () {
    config(['services.openwa.webhook_secret' => 'super-secret-value']);

    $response = $this->postJson('/webhook/api', [
        'event' => 'message.received',
    ]);

    $response->assertForbidden();
});

it('accepts a correctly signed request when a webhook secret is configured', function () {
    config(['services.openwa.webhook_secret' => 'super-secret-value']);

    $payload = ['event' => 'message.received', 'sessionId' => 'session-123'];
    $body = json_encode($payload);
    $signature = 'sha256='.hash_hmac('sha256', $body, 'super-secret-value');

    $response = $this->call(
        'POST',
        '/webhook/api',
        [],
        [],
        [],
        ['HTTP_X-OpenWA-Signature' => $signature, 'CONTENT_TYPE' => 'application/json'],
        $body
    );

    $response->assertOk()->assertJson(['received' => true]);
});

it('rejects a request with a bad signature', function () {
    config(['services.openwa.webhook_secret' => 'super-secret-value']);

    $response = $this->call(
        'POST',
        '/webhook/api',
        [],
        [],
        [],
        ['HTTP_X-OpenWA-Signature' => 'sha256=invalid', 'CONTENT_TYPE' => 'application/json'],
        json_encode(['event' => 'message.received'])
    );

    $response->assertForbidden();
});

it('requires the event field', function () {
    $response = $this->postJson('/webhook/api', []);

    $response->assertUnprocessable()->assertJsonValidationErrors('event');
});

it('accepts every documented event type', function (string $event) {
    $response = $this->postJson('/webhook/api', ['event' => $event]);

    $response->assertOk()->assertJson(['received' => true]);
})->with(array_column(OpenWAWebhookEvent::cases(), 'value'));

it('still accepts and logs an unrecognized event type without erroring', function () {
    Log::spy();

    $response = $this->postJson('/webhook/api', ['event' => 'some.future.event']);

    $response->assertOk()->assertJson(['received' => true]);

    Log::shouldHaveReceived('info')->once()->withArgs(
        fn (string $message, array $context) => $context['event'] === 'some.future.event'
            && $context['recognized'] === false
    );
});
