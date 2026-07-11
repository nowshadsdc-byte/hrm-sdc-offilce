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
            if (! Schema::hasColumn('attendances', 'lunch_start')) {
                $table->timestamp('lunch_start')->nullable()->after('check_in');
            }

            if (! Schema::hasColumn('attendances', 'lunch_end')) {
                $table->timestamp('lunch_end')->nullable()->after('lunch_start');
            }

            if (! Schema::hasColumn('attendances', 'lunch_duration_minutes')) {
                $table->unsignedInteger('lunch_duration_minutes')->nullable()->after('lunch_end');
            }

            if (! Schema::hasColumn('attendances', 'total_work_minutes')) {
                $table->unsignedInteger('total_work_minutes')->nullable()->after('check_out');
            }

            if (! Schema::hasColumn('attendances', 'overtime_minutes')) {
                $table->unsignedInteger('overtime_minutes')->nullable()->after('total_work_minutes');
            }

            if (! Schema::hasColumn('attendances', 'late_status')) {
                $table->boolean('late_status')->default(false)->after('overtime_minutes');
            }

            if (! Schema::hasColumn('attendances', 'late_duration_minutes')) {
                $table->unsignedInteger('late_duration_minutes')->default(0)->after('late_status');
            }

            if (! Schema::hasColumn('attendances', 'last_raw_punch_at')) {
                $table->timestamp('last_raw_punch_at')->nullable()->after('record_time_only');
            }

            if (! Schema::hasColumn('attendances', 'raw_punch_signature')) {
                $table->string('raw_punch_signature')->nullable()->after('last_raw_punch_at');
            }

            if (! Schema::hasColumn('attendances', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('raw_punch_signature');
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $columns = [
                'lunch_start',
                'lunch_end',
                'lunch_duration_minutes',
                'total_work_minutes',
                'overtime_minutes',
                'late_status',
                'late_duration_minutes',
                'last_raw_punch_at',
                'raw_punch_signature',
                'last_synced_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
