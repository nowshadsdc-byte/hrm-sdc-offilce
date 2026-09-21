<?php

use Illuminate\Support\Facades\Http;

it('returns profile picture urls for the requested chat ids', function () {
    Http::fake([
        '*/api/sessions/*/contacts/profile-pictures*' => Http::response([
            'pictures' => [
                '108199034232960@lid' => null,
                '16785420099740@lid' => 'https://pps.whatsapp.net/v/photo.jpg',
            ],
        ]),
    ]);

    $response = $this->getJson('/api/openwa/profile-pictures?ids=108199034232960@lid,16785420099740@lid');

    $response->assertOk()->assertJson([
        'pictures' => [
            '108199034232960@lid' => null,
            '16785420099740@lid' => 'https://pps.whatsapp.net/v/photo.jpg',
        ],
    ]);
});

it('chunks more than 50 ids into multiple upstream requests', function () {
    Http::fake([
        '*/api/sessions/*/contacts/profile-pictures*' => Http::sequence()
            ->push(['pictures' => array_fill_keys(array_map(fn ($n) => "id{$n}", range(1, 50)), 'https://example.test/a.jpg')])
            ->push(['pictures' => ['id51' => 'https://example.test/b.jpg']]),
    ]);

    $ids = implode(',', array_map(fn ($n) => "id{$n}", range(1, 51)));

    $response = $this->getJson("/api/openwa/profile-pictures?ids={$ids}");

    $response->assertOk();
    $response->assertJsonPath('pictures.id1', 'https://example.test/a.jpg');
    $response->assertJsonPath('pictures.id51', 'https://example.test/b.jpg');

    Http::assertSentCount(2);
});

it('requires the ids parameter', function () {
    $response = $this->getJson('/api/openwa/profile-pictures');

    $response->assertUnprocessable()->assertJsonValidationErrors('ids');
});

it('loads a chat history from the documented history endpoint', function () {
    Http::fake([
        '*/api/sessions/session-one/messages/chat-one/history*' => Http::response([
            'messages' => [['body' => 'Hello']],
        ]),
    ]);

    $response = $this->getJson('/api/openwa/chats/chat-one/messages?session_id=session-one');

    $response->assertOk()->assertJsonPath('messages.0.body', 'Hello');
    Http::assertSent(fn ($request) => str_contains($request->url(), '/api/sessions/session-one/messages/chat-one/history'));
});

it('loads chat profile details from the selected session', function () {
    Http::fake([
        '*/api/sessions/session-one/chats/chat-one*' => Http::response([
            'name' => 'Jane Doe',
            'number' => '+8801700000000',
        ]),
    ]);

    $response = $this->getJson('/api/openwa/chats/chat-one?session_id=session-one');

    $response->assertOk()->assertJsonPath('number', '+8801700000000');
    Http::assertSent(fn ($request) => str_contains($request->url(), '/api/sessions/session-one/chats/chat-one'));
});

it('proxies message media using the selected session', function () {
    Http::fake([
        '*/api/sessions/session-one/messages/chat-one/message-one/media' => Http::response('media bytes', 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    $response = $this->get('/api/openwa/chats/chat-one/messages/message-one/media?session_id=session-one');

    $response->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertSee('media bytes');
});
