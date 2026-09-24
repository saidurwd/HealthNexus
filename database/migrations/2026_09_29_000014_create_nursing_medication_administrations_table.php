<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anchored to pharmacy_dispensing_items (decision #7 of the Phase 9 plan) — PrescriptionItem
     * is free-text with no structured medication_id/dose/route, so the real traceable chain only
     * begins at dispensing. medication_id/batch_id are denormalized alongside dispensing_item_id
     * for query convenience, mirroring how PharmacyDispensingItem itself already denormalizes
     * order_item_id+medication_id+batch_id rather than forcing every read through a join chain.
     */
    public function up(): void
    {
        Schema::create('nursing_medication_administrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('nursing_episodes')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions')->nullOnDelete();
            $table->foreignId('prescription_item_id')->nullable()->constrained('prescription_items')->nullOnDelete();
            $table->foreignId('dispensing_item_id')->nullable()->constrained('pharmacy_dispensing_items')->nullOnDelete();
            $table->foreignId('medication_id')->nullable()->constrained('pharmacy_medications')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('pharmacy_batches')->nullOnDelete();
            $table->timestamp('scheduled_at');
            $table->timestamp('administered_at')->nullable();
            $table->string('dose')->nullable();
            $table->string('dose_unit')->nullable();
            $table->string('route')->nullable();
            $table->string('site')->nullable();
            $table->string('status')->default('scheduled');
            $table->string('reason_if_not_administered')->nullable();
            $table->boolean('is_prn')->default(false);
            $table->string('prn_reason')->nullable();
            $table->foreignId('administered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('witnessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('safety_checks')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('superseded_by_correction_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['episode_id', 'status', 'scheduled_at']);
            $table->index(['admission_id', 'status']);
            $table->index(['dispensing_item_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_medication_administrations');
    }
};
