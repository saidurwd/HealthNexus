<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Observation Service store (guideline 10.4, H-03). One measurement = one row; panels group rows. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_observation_sets', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('context_type', 20);
            $table->unsignedBigInteger('context_id')->nullable();
            $table->string('panel_code', 40);
            $table->timestamp('effective_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'effective_at'], 'idx_obs_sets_patient_time');
        });

        Schema::create('clinical_observations', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('context_type', 20);                    // opd, ipd, icu, ed, nursing, ot
            $table->unsignedBigInteger('context_id')->nullable();
            $table->foreignId('observation_set_id')->nullable()->constrained('clinical_observation_sets')->nullOnDelete();
            $table->string('code_system', 30)->default('loinc');
            $table->string('code', 64);
            $table->string('display', 255);
            $table->decimal('value_number', 18, 4)->nullable();
            $table->text('value_text')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->string('unit_code', 40)->nullable();           // UCUM
            $table->timestamp('effective_at');
            $table->timestamp('recorded_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device_id', 64)->nullable();
            $table->string('status', 20)->default('final');         // preliminary, final, amended, entered_in_error
            $table->string('interpretation', 20)->nullable();      // low, normal, high, critical_low, critical_high
            $table->decimal('reference_low', 18, 4)->nullable();
            $table->decimal('reference_high', 18, 4)->nullable();
            $table->foreignId('supersedes_id')->nullable()->constrained('clinical_observations')->nullOnDelete();
            $table->string('error_reason')->nullable();
            $table->string('source_module', 20);
            $table->string('source_record_id', 40)->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'code', 'effective_at'], 'idx_clin_obs_patient_code_time');
            $table->index(['organization_id', 'encounter_id', 'effective_at'], 'idx_clin_obs_encounter_time');
            $table->index(['context_type', 'context_id'], 'idx_clin_obs_context');
            $table->unique(['source_module', 'source_record_id', 'code'], 'uq_clin_obs_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_observations');
        Schema::dropIfExists('clinical_observation_sets');
    }
};
