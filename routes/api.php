<?php

use App\Http\Controllers\MessageTemplateController;
use App\Services\OpenWAService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/openwa/sessions', function (OpenWAService $openwa) {
    return response()->json(
        $openwa->getSessions()
    );
});

Route::get('/openwa/chats', function (OpenWAService $openwa) {
    $sessionId = request()->string('session_id')->trim()->value()
        ?: config('services.openwa.session_id');

    return response()->json(
        $openwa->getChats(
            $sessionId
        )
    );
});

Route::get('/openwa/chats/{chatId}', function (Request $request, string $chatId, OpenWAService $openwa) {
    $sessionId = $request->string('session_id')->trim()->value()
        ?: config('services.openwa.session_id');

    return response()->json(
        $openwa->getChat($sessionId, $chatId)
    );
});

Route::get('/openwa/chats/{chatId}/messages', function (Request $request, string $chatId, OpenWAService $openwa) {
    $sessionId = $request->string('session_id')->trim()->value()
        ?: config('services.openwa.session_id');

    return response()->json(
        $openwa->getMessages(
            $sessionId,
            $chatId
        )
    );
});

Route::get('/openwa/chats/{chatId}/messages/{messageId}/media', function (
    Request $request,
    string $chatId,
    string $messageId,
    OpenWAService $openwa
) {
    $sessionId = $request->string('session_id')->trim()->value()
        ?: config('services.openwa.session_id');
    $media = $openwa->getMedia($sessionId, $chatId, $messageId);

    return response($media->body(), $media->status())
        ->header('Content-Type', $media->header('Content-Type') ?? 'application/octet-stream')
        ->header('Content-Disposition', $media->header('Content-Disposition') ?? 'inline');
});

Route::get('/openwa/profile-pictures', function (Request $request, OpenWAService $openwa) {
    $validated = $request->validate([
        'ids' => ['required', 'string'],
    ]);

    $ids = array_values(array_filter(array_map('trim', explode(',', $validated['ids']))));
    $sessionId = $request->string('session_id')->trim()->value()
        ?: config('services.openwa.session_id');

    return response()->json([
        'pictures' => $openwa->getProfilePictures(
            $sessionId,
            $ids
        ),
    ]);
});

Route::post('/openwa/send-message', function (
    Request $request,
    OpenWAService $openwa
) {
    $validated = $request->validate([
        'chatId' => ['required', 'string'],
        'text' => ['required', 'string'],
        'sessionId' => ['nullable', 'string'],
    ]);

    return response()->json(
        $openwa->sendText(
            $validated['sessionId'] ?? config('services.openwa.session_id'),
            $validated['chatId'],
            $validated['text']
        )
    );
});

Route::post('/openwa/send-media', function (
    Request $request,
    OpenWAService $openwa
) {
    $validated = $request->validate([
        'chatId' => ['required', 'string'],
        'base64' => ['required', 'string'],
        'mimetype' => ['required', 'string'],
        'filename' => ['nullable', 'string', 'max:255'],
        'caption' => ['nullable', 'string', 'max:1024'],
        'sessionId' => ['nullable', 'string'],
    ]);

    $type = match (true) {
        str_starts_with($validated['mimetype'], 'image/') => 'image',
        str_starts_with($validated['mimetype'], 'video/') => 'video',
        str_starts_with($validated['mimetype'], 'audio/') => 'audio',
        default => 'document',
    };

    return response()->json(
        $openwa->sendMedia(
            $validated['sessionId'] ?? config('services.openwa.session_id'),
            $validated['chatId'],
            $type,
            $validated['base64'],
            $validated['mimetype'],
            $validated['filename'] ?? null,
            $validated['caption'] ?? null
        )
    );
});

Route::apiResource('message-templates', MessageTemplateController::class)
    ->only(['index', 'store', 'destroy']);
