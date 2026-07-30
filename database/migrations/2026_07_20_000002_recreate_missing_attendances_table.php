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
        if (Schema::hasTable('attendances')) {
            return;
        }

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamp('check_in')->nullable();
            $table->timestamp('lunch_start')->nullable();
            $table->timestamp('lunch_end')->nullable();
            $table->timestamp('check_out')->nullable();
            $table->date('date')->nullable();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();

            $table->string('device_user_id')->nullable();
            $table->string('employee_name')->nullable();
            $table->dateTime('record_time')->nullable();
            $table->string('record_date')->nullable();
            $table->string('record_time_only')->nullable();
            $table->string('timezone')->nullable();

            $table->unsignedInteger('lunch_duration_minutes')->nullable();
            $table->unsignedInteger('total_work_minutes')->nullable();
            $table->unsignedInteger('overtime_minutes')->nullable();
            $table->boolean('late_status')->default(false);
            $table->unsignedInteger('late_duration_minutes')->default(0);
            $table->string('remarks')->nullable();
            $table->timestamp('last_raw_punch_at')->nullable();
            $table->string('raw_punch_signature')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->unique(['device_user_id', 'record_time'], 'unique_attendance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
