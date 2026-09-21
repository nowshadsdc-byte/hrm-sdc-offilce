<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OpenWAService
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $sessionId;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.openwa.base_url'),
            '/'
        );
        $this->sessionId = config('services.openwa.session_id');

        $this->apiKey = config('services.openwa.api_key');
    }

    protected function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->connectTimeout(3)
            ->timeout(10);
    }

    public function getSessions()
    {
        return $this->client()
            ->get('/api/sessions')
            ->throw()
            ->json();
    }

    public function getChats(
        string $sessionId,
        int $limit = 100,
        int $offset = 0
    ) {
        return $this->client()
            ->get("/api/sessions/{$sessionId}/chats", [
                'limit' => $limit,
                'offset' => $offset,
            ])
            ->throw()
            ->json();
    }

    public function getChat(string $sessionId, string $chatId)
    {
        return $this->client()
            ->get("/api/sessions/{$sessionId}/chats/{$chatId}")
            ->throw()
            ->json();
    }

    public function getMessages(
        string $sessionId,
        string $chatId,
        int $limit = 50
    ) {
        return $this->client()
            ->get("/api/sessions/{$sessionId}/messages/{$chatId}/history", [
                'limit' => $limit,
            ])
            ->throw()
            ->json();
    }

    public function getMedia(
        string $sessionId,
        string $chatId,
        string $messageId
    ): Response {
        return $this->client()
            ->get("/api/sessions/{$sessionId}/messages/{$chatId}/{$messageId}/media")
            ->throw();
    }

    /**
     * @param  array<int, string>  $ids
     * @return array<string, string|null>
     */
    public function getProfilePictures(string $sessionId, array $ids): array
    {
        $pictures = [];

        foreach (array_chunk(array_values(array_unique($ids)), 50) as $chunk) {
            $response = $this->client()
                ->get("/api/sessions/{$sessionId}/contacts/profile-pictures", [
                    'ids' => implode(',', $chunk),
                ])
                ->throw()
                ->json();

            $pictures = array_merge($pictures, $response['pictures'] ?? []);
        }

        return $pictures;
    }

    public function sendMedia(
        string $sessionId,
        string $chatId,
        string $type,
        string $base64,
        string $mimetype,
        ?string $filename = null,
        ?string $caption = null
    ) {
        return $this->client()
            ->post(
                "/api/sessions/{$sessionId}/messages/send-{$type}",
                array_filter([
                    'chatId' => $chatId,
                    'base64' => $base64,
                    'mimetype' => $mimetype,
                    'filename' => $filename,
                    'caption' => $caption,
                ], fn ($value) => $value !== null)
            )
            ->throw()
            ->json();
    }

    public function sendText(
        string $sessionId,
        string $chatId,
        string $text
    ) {
        return $this->client()
            ->post(
                "/api/sessions/{$sessionId}/messages/send-text",
                [
                    'chatId' => $chatId,
                    'text' => $text,
                ]
            )
            ->throw()
            ->json();
    }
}
