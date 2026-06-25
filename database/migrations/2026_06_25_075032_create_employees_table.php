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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
               // Profile
            $table->string('name');
            $table->string('nid')->nullable()->index();
            $table->date('dob')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();

            // Job details
            $table->string('job_title')->nullable();
            $table->date('job_join_date')->nullable();
            $table->text('job_description')->nullable();

            // Documents (stored as file paths)
            $table->string('nid_file')->nullable();
            $table->string('certificate_file')->nullable();
            $table->string('contract_file')->nullable();

            // Organization
            $table->string('department')->nullable();
            $table->string('designation')->nullable();
            $table->string('role')->nullable();
            
            // Status 
            $table->string('status')->default('active');
            $table->text('transfer_promotion_notes')->nullable();
            $table->string('separation_type')->nullable(); // resignation, termination, etc.
            $table->date('separation_date')->nullable();
            $table->boolean('clearance_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
