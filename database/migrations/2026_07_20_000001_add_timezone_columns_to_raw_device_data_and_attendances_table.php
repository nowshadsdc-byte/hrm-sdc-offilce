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
        Schema::table('rawDeviceData', function (Blueprint $table) {
            if (! Schema::hasColumn('rawDeviceData', 'timeZone')) {
                $table->string('timeZone')->nullable()->after('recordTime');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'timezone')) {
                $table->string('timezone')->nullable()->after('record_time_only');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rawDeviceData', function (Blueprint $table) {
            $table->dropColumn('timeZone');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
