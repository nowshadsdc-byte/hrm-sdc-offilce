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
            if (! Schema::hasColumn('attendances', 'early_leave_status')) {
                $table->boolean('early_leave_status')->default(false)->after('late_duration_minutes');
            }

            if (! Schema::hasColumn('attendances', 'early_leave_minutes')) {
                $table->unsignedInteger('early_leave_minutes')->default(0)->after('early_leave_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['early_leave_status', 'early_leave_minutes']);
        });
    }
};
