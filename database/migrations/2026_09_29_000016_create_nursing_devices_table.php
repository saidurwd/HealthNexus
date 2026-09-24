<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clinical device tracking, not enterprise asset tracking (spec §37) — no purchase/warranty/
     * serial-inventory fields, only what a bedside nurse needs to monitor an inserted line/tube.
     */
    public function up(): void
    {
        Schema::create('nursing_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('nursing_episodes')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('device_type');
            $table->date('insertion_date');
            $table->string('site')->nullable();
            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active');
            $table->string('care_schedule')->nullable();
            $table->timestamp('last_assessment_at')->nullable();
            $table->date('removal_date')->nullable();
            $table->foreignId('removal_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('complication')->nullable();
            $table->timestamps();

            $table->index(['episode_id', 'status']);
            $table->index(['admission_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_devices');
    }
};
