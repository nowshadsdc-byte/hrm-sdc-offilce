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
        Schema::table('devices', function (Blueprint $table) {
            $table->enum('connection_type', ['api', 'adms'])->default('adms')->after('device_type');
            $table->string('api_endpoint')->nullable()->after('ip_address');
            $table->string('api_url')->nullable()->after('api_endpoint');
            $table->string('ip_address')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['connection_type', 'api_endpoint', 'api_url']);
            $table->string('ip_address')->nullable(false)->change();
        });
    }
};
