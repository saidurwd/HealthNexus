<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Self-contained — works identically for IPD inpatients and pure day-cases (decision #6).
     * Recovery vitals are NOT stored here; they reuse the shared vital_signs table directly via
     * EncounterClinicalService::addVital($surgery->encounter, ...).
     */
    public function up(): void
    {
        Schema::create('ot_recovery_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('ot_surgeries')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->timestamp('arrived_at');
            $table->string('status')->default('in_recovery');
            $table->string('pain_score')->nullable();
            $table->string('consciousness')->nullable();
            $table->string('airway_status')->nullable();
            $table->string('nausea_vomiting')->nullable();
            $table->text('complications')->nullable();
            $table->boolean('discharge_criteria_met')->default(false);
            $table->string('destination')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('discharged_at')->nullable();
            $table->timestamps();

            $table->unique('surgery_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_recovery_records');
    }
};
