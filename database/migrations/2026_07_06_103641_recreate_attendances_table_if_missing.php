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
            $table->timestamp('check_out')->nullable();
            $table->date('date')->nullable();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();

            // Device API import fields.
            $table->string('device_user_id');
            $table->string('employee_name');
            $table->dateTime('record_time');
            $table->string('record_date');
            $table->string('record_time_only');

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
