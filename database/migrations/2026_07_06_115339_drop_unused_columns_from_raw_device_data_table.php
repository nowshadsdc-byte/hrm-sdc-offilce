<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE rawDeviceData DROP INDEX rawdevicedata_device_user_id_work_date_unique');
        } catch (Throwable $e) {
            // Legacy index may already be dropped.
        }

        try {
            DB::statement('ALTER TABLE rawDeviceData DROP INDEX rawdevicedata_device_user_id_date_index');
        } catch (Throwable $e) {
            // Optional index may not exist in all environments.
        }

        Schema::table('rawDeviceData', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('rawDeviceData', 'device_user_id')) {
                $columnsToDrop[] = 'device_user_id';
            }

            if (Schema::hasColumn('rawDeviceData', 'work_date')) {
                $columnsToDrop[] = 'work_date';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rawDeviceData', function (Blueprint $table) {
            if (! Schema::hasColumn('rawDeviceData', 'device_user_id')) {
                $table->string('device_user_id')->nullable()->after('deviceUserId');
            }

            if (! Schema::hasColumn('rawDeviceData', 'work_date')) {
                $table->date('work_date')->nullable()->after('date');
            }
        });

        try {
            DB::statement('ALTER TABLE rawDeviceData ADD INDEX rawdevicedata_device_user_id_date_index (device_user_id, `date`)');
        } catch (Throwable $e) {
            // Ignore if index exists or columns are missing.
        }
    }
};
