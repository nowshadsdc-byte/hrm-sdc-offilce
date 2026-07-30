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
        if (! Schema::hasColumn('shifts', 'is_default')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('end_time');
            });
        }

        if (! Schema::hasColumn('employees', 'shift_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('shift_id')->nullable()->after('device_cardno')->constrained('shifts')->nullOnDelete();
            });
        }

        $defaultShiftId = DB::table('shifts')
            ->where('start_time', '10:30:00')
            ->where('end_time', '18:00:00')
            ->value('id');

        if ($defaultShiftId === null) {
            $defaultShiftId = DB::table('shifts')->insertGetId([
                'name' => 'Default Shift',
                'start_time' => '10:30:00',
                'end_time' => '18:00:00',
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('shifts')->where('id', '!=', $defaultShiftId)->update(['is_default' => false]);
        DB::table('shifts')->where('id', $defaultShiftId)->update(['is_default' => true]);

        DB::table('employees')->whereNull('shift_id')->update(['shift_id' => $defaultShiftId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'shift_id')) {
                $table->dropConstrainedForeignId('shift_id');
            }
        });

        Schema::table('shifts', function (Blueprint $table) {
            if (Schema::hasColumn('shifts', 'is_default')) {
                $table->dropColumn('is_default');
            }
        });
    }
};
