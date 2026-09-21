<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

class OpenWAWebhookEventRecord extends Model
{
    protected $table = 'openwa_webhook_events';

    protected $fillable = [
        'event',
        'session_id',
        'client_id',
        'event_id',
        'chat_id',
        'message_id',
        'payload',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => AsArrayObject::class,
            'received_at' => 'datetime',
        ];
    }
}
