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
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'device_user_id')) {
                $table->string('device_user_id')->nullable()->after('device_id');
            }

            if (! Schema::hasColumn('attendances', 'employee_name')) {
                $table->string('employee_name')->nullable()->after('device_user_id');
            }

            if (! Schema::hasColumn('attendances', 'record_time')) {
                $table->dateTime('record_time')->nullable()->after('employee_name');
            }

            if (! Schema::hasColumn('attendances', 'record_date')) {
                $table->string('record_date')->nullable()->after('record_time');
            }

            if (! Schema::hasColumn('attendances', 'record_time_only')) {
                $table->string('record_time_only')->nullable()->after('record_date');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['device_user_id', 'record_time'], 'unique_attendance');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('unique_attendance');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'device_user_id',
                'employee_name',
                'record_time',
                'record_date',
                'record_time_only',
            ]);
        });
    }
};
