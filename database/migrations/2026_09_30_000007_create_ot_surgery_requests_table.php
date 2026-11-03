<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_surgery_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('ipd_admissions')->nullOnDelete();
            $table->foreignId('clinical_order_id')->nullable()->constrained('clinical_orders')->nullOnDelete();
            $table->foreignId('requesting_provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('specialty_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('ot_procedures')->nullOnDelete();
            $table->text('indication')->nullable();
            $table->string('site')->nullable();
            $table->string('laterality')->nullable();
            $table->foreignId('diagnosis_id')->nullable()->constrained('diagnoses')->nullOnDelete();
            $table->string('priority')->default('routine');
            $table->string('surgery_type')->default('elective');
            $table->date('preferred_date')->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->string('required_theatre_capability')->nullable();
            $table->string('anesthesia_requirement')->nullable();
            $table->string('special_equipment')->nullable();
            $table->boolean('implant_requirement')->default(false);
            $table->boolean('blood_requirement')->default(false);
            $table->boolean('specimen_requirement')->default(false);
            $table->text('notes')->nullable();
            $table->string('status')->default('requested');
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'branch_id', 'status']);
            $table->index('patient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_surgery_requests');
    }
};
