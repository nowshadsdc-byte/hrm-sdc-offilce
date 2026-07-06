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
        Schema::table('rawDeviceData', function (Blueprint $table) {
            if (! Schema::hasColumn('rawDeviceData', 'device_user_id')) {
                $table->string('device_user_id')->nullable()->after('deviceUserId');
            }

            if (! Schema::hasColumn('rawDeviceData', 'recordTime')) {
                $table->dateTime('recordTime')->nullable()->after('time');
            }

            if (! Schema::hasColumn('rawDeviceData', 'uniqueKey')) {
                $table->string('uniqueKey')->nullable()->after('recordTime');
            }
        });

        DB::statement('UPDATE rawDeviceData SET device_user_id = COALESCE(device_user_id, deviceUserId)');
        DB::statement("UPDATE rawDeviceData SET recordTime = STR_TO_DATE(CONCAT(`date`, ' ', `time`), '%Y-%m-%d %H:%i:%s') WHERE recordTime IS NULL AND `date` IS NOT NULL AND `time` IS NOT NULL");
        DB::statement("UPDATE rawDeviceData SET uniqueKey = CONCAT(device_user_id, '|', DATE_FORMAT(recordTime, '%Y-%m-%d %H:%i:%s')) WHERE device_user_id IS NOT NULL AND recordTime IS NOT NULL");

        DB::statement('DELETE r1 FROM rawDeviceData r1 INNER JOIN rawDeviceData r2 ON r1.uniqueKey = r2.uniqueKey AND r1.id > r2.id WHERE r1.uniqueKey IS NOT NULL');

        try {
            DB::statement('ALTER TABLE rawDeviceData DROP INDEX rawdevicedata_device_user_id_work_date_unique');
        } catch (Throwable $e) {
            // Legacy index may not exist on all environments.
        }

        try {
            Schema::table('rawDeviceData', function (Blueprint $table) {
                $table->unique('uniqueKey');
            });
        } catch (Throwable $e) {
            // Unique index may already exist from partially applied migration.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rawDeviceData', function (Blueprint $table) {
            $table->dropUnique(['uniqueKey']);
            $table->dropColumn(['device_user_id', 'recordTime', 'uniqueKey']);
        });
    }
};
