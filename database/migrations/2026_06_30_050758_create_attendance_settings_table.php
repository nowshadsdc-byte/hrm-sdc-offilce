<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();

            // Working hours
            $table->time('working_hours_start')->default('09:00:00');
            $table->time('working_hours_end')->default('18:00:00');

            // Weekend configuration - stored as JSON array of days
            // e.g. ["fri", "sat"]
            $table->json('weekend_days')->nullable();

            // Timezone
            $table->string('timezone')->default('Asia/Dhaka');

            // Backup & Restore settings
            $table->boolean('auto_backup_enabled')->default(false);
            $table->string('backup_frequency')->nullable(); // daily, weekly, monthly
            $table->string('backup_path')->nullable();
            $table->timestamp('last_backup_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
