<?php

use Illuminate\Support\Facades\Http;

it('sends an image through the send-image endpoint based on mimetype', function () {
    Http::fake([
        '*/api/sessions/*/messages/send-image' => Http::response(['id' => 'msg-1'], 201),
    ]);

    $response = $this->postJson('/api/openwa/send-media', [
        'chatId' => '108199034232960@lid',
        'base64' => base64_encode('fake-image-bytes'),
        'mimetype' => 'image/png',
        'filename' => 'photo.png',
    ]);

    $response->assertOk()->assertJson(['id' => 'msg-1']);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/messages/send-image')
        && $request['chatId'] === '108199034232960@lid'
        && $request['mimetype'] === 'image/png'
        && $request['filename'] === 'photo.png');
});

it('sends a non-media file through the send-document endpoint', function () {
    Http::fake([
        '*/api/sessions/*/messages/send-document' => Http::response(['id' => 'msg-2'], 201),
    ]);

    $response = $this->postJson('/api/openwa/send-media', [
        'chatId' => '108199034232960@lid',
        'base64' => base64_encode('fake-pdf-bytes'),
        'mimetype' => 'application/pdf',
        'filename' => 'invoice.pdf',
    ]);

    $response->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/messages/send-document'));
});

it('requires chatId, base64 and mimetype', function () {
    $response = $this->postJson('/api/openwa/send-media', []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['chatId', 'base64', 'mimetype']);
});
