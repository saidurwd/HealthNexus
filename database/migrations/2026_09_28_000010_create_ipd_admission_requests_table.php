<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipd_admission_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_encounter_id')->nullable()->constrained('encounters')->nullOnDelete();
            $table->foreignId('requesting_provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('specialty_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_type_id')->nullable()->constrained('ipd_admission_types')->nullOnDelete();
            $table->foreignId('admission_source_id')->nullable()->constrained('ipd_admission_sources')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->text('provisional_diagnosis')->nullable();
            $table->string('priority')->default('routine');
            $table->unsignedInteger('expected_length_of_stay_days')->nullable();
            $table->date('expected_admission_date')->nullable();
            $table->date('expected_discharge_date')->nullable();
            $table->foreignId('required_bed_type_id')->nullable()->constrained('ipd_bed_types')->nullOnDelete();
            $table->string('isolation_requirement')->nullable();
            $table->text('special_requirements')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('requested');
            $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index('patient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_admission_requests');
    }
};
