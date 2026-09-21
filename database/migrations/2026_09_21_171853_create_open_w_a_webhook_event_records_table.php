<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('openwa_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event')->index();
            $table->string('session_id')->nullable()->index();
            $table->string('event_id')->nullable()->index();
            $table->string('chat_id')->nullable()->index();
            $table->string('message_id')->nullable()->index();
            $table->json('payload');
            $table->timestamp('received_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('openwa_webhook_events');
    }
};
