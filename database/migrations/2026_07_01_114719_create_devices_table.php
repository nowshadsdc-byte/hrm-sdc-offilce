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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('device_type')->default('fingerprint'); // fingerprint, face_recognition
            $table->string('ip_address');
            $table->unsignedInteger('port')->default(4370);
            $table->string('serial_number')->unique();
            $table->string('location')->nullable();
            $table->unsignedInteger('sync_interval_minutes')->default(10);
            $table->boolean('auto_sync')->default(true);
            $table->enum('status', ['online', 'offline', 'error'])->default('offline');
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable(); // useful for the "Needs Attention" state
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
