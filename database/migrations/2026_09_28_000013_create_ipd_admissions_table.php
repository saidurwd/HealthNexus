<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipd_admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('admission_number');
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admission_request_id')->nullable()->constrained('ipd_admission_requests')->nullOnDelete();
            $table->foreignId('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->foreignId('admission_type_id')->nullable()->constrained('ipd_admission_types')->nullOnDelete();
            $table->foreignId('admission_source_id')->nullable()->constrained('ipd_admission_sources')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('specialty_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admitting_provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('attending_provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->timestamp('admitted_at');
            $table->date('expected_discharge_date')->nullable();
            $table->timestamp('actual_discharge_date')->nullable();
            $table->string('priority')->default('routine');
            $table->string('status')->default('admitted');
            $table->foreignId('discharge_disposition_id')->nullable()->constrained('ipd_discharge_dispositions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('admitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('discharged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'admission_number']);
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index('patient_id');
            $table->index('expected_discharge_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_admissions');
    }
};
