<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lines/tubes/drains consolidated into one generic table (Phase 11 plan decision #12) —
     * mirrors NursingDevice's proven free-string device_type shape exactly, just ICU-scoped.
     */
    public function up(): void
    {
        Schema::create('icu_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('device_type');
            $table->date('insertion_date')->nullable();
            $table->string('site')->nullable();
            $table->string('side')->nullable();
            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('indication')->nullable();
            $table->string('status')->default('active');
            $table->string('care_schedule')->nullable();
            $table->timestamp('last_assessment_at')->nullable();
            $table->date('removal_date')->nullable();
            $table->foreignId('removal_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('complication')->nullable();
            $table->timestamps();

            $table->index(['episode_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_devices');
    }
};
