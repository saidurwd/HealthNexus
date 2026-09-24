<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nursing clearance only — Phase 8 (ipd_discharge_requests) remains the operational discharge
     * owner; this table is never written to by Phase 8 code and never writes to it (spec §49).
     */
    public function up(): void
    {
        Schema::create('nursing_discharge_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('nursing_episodes')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discharge_request_id')->nullable()->constrained('ipd_discharge_requests')->nullOnDelete();
            $table->boolean('education_completed')->default(false);
            $table->boolean('medication_education_completed')->default(false);
            $table->boolean('devices_removed')->default(false);
            $table->boolean('belongings_confirmed')->default(false);
            $table->boolean('follow_up_instructions_given')->default(false);
            $table->string('status')->default('pending');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['episode_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_discharge_checklists');
    }
};
