<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icu_episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('episode_number');
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained('encounters')->cascadeOnDelete();
            // Nullable + not unique: an ICU episode may be created before the hospital-wide
            // admission exists (IcuEpisodeService creates one via IpdAdmissionService), and one
            // admission may have more than one ICU episode over its stay (ICU -> step-down -> ICU).
            $table->foreignId('admission_id')->nullable()->constrained('ipd_admissions')->nullOnDelete();
            $table->foreignId('admission_request_id')->nullable()->constrained('icu_admission_requests')->nullOnDelete();
            $table->foreignId('icu_unit_id')->nullable()->constrained('ipd_wards')->nullOnDelete();
            $table->foreignId('icu_bed_id')->nullable()->constrained('ipd_beds')->nullOnDelete();
            $table->text('admission_reason')->nullable();
            $table->foreignId('primary_diagnosis_id')->nullable()->constrained('diagnoses')->nullOnDelete();
            $table->foreignId('admitting_provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('attending_provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->string('priority')->default('routine');
            $table->string('status')->default('admitted');
            // Phase 13 (Blood Bank) does not exist yet — forward-compatible placeholder only,
            // deferred exactly like OT deferred the same integration in Phase 10.
            $table->string('blood_requirement')->nullable();
            $table->timestamp('admitted_at')->nullable();
            $table->timestamp('discharged_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'episode_number']);
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index('patient_id');
            $table->index('admission_id');
            $table->index('icu_bed_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_episodes');
    }
};
