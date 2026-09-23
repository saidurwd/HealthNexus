<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('radiology_order_items')->cascadeOnDelete();
            $table->foreignId('modality_id')->nullable()->constrained('radiology_modalities')->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            // The scheduled Appointment row (Phase 6 plan decision #1) — nullable because an
            // examination can be performed as a walk-in without a prior scheduled slot.
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            // scheduled|checked_in|preparing|ready|in_progress|completed|cancelled|no_show
            $table->string('status')->default('scheduled');
            $table->foreignId('technologist_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('performing_provider_id')->nullable()->constrained('providers')->nullOnDelete();

            // Radiologist assignment — folded into the examination rather than a separate
            // radiology_assignments table (plan decision #6).
            $table->foreignId('assigned_radiologist_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('clinical_notes')->nullable();
            $table->text('technical_notes')->nullable();

            // MRI safety screening — a hospital-configurable checklist (spec §25), stored as
            // answered items rather than hardcoded clinical rules.
            $table->json('mri_safety_screening')->nullable();
            $table->foreignId('mri_screened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('mri_screened_at')->nullable();

            // Contrast administration record (spec §26) — RIS records administration only, never
            // inventory (see radiology_contrast_agents).
            $table->foreignId('contrast_agent_id')->nullable()->constrained('radiology_contrast_agents')->nullOnDelete();
            $table->string('contrast_route')->nullable();
            $table->string('contrast_dose')->nullable();
            $table->timestamp('contrast_administered_at')->nullable();
            $table->foreignId('contrast_administered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('contrast_reaction')->nullable();
            $table->string('contrast_reaction_severity')->nullable();
            $table->text('contrast_action_taken')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['patient_id']);
            $table->index(['modality_id']);
            $table->index(['assigned_radiologist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_examinations');
    }
};
