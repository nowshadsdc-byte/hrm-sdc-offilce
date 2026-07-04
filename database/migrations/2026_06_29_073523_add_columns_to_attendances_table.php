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
            $table->foreignId('employee_id')
                ->nullable()
                ->constrained('employees')
                ->onDelete('cascade')
                ->after('id');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade')
                ->after('employee_id');

            $table->timestamp('check_in')->nullable()->after('user_id');
            $table->timestamp('check_out')->nullable()->after('check_in');
            $table->date('date')->nullable()->after('check_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['employee_id']);
            $table->dropForeignKeyIfExists(['user_id']);
            $table->dropColumn(['employee_id', 'user_id', 'check_in', 'check_out', 'date']);
        });
    }
};
