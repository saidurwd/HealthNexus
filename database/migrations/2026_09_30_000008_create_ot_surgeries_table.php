<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_surgeries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('surgery_number');
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('ipd_admissions')->nullOnDelete();
            $table->foreignId('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->foreignId('surgery_request_id')->nullable()->constrained('ot_surgery_requests')->nullOnDelete();
            $table->foreignId('primary_procedure_id')->nullable()->constrained('ot_procedures')->nullOnDelete();
            $table->foreignId('specialty_id')->nullable()->constrained()->nullOnDelete();
            $table->string('priority')->default('routine');
            $table->string('surgery_type')->default('elective');
            $table->string('site')->nullable();
            $table->string('laterality')->nullable();
            $table->foreignId('theatre_id')->nullable()->constrained('ot_theatres')->nullOnDelete();
            $table->date('scheduled_date')->nullable();
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();
            $table->string('status')->default('requested');
            $table->foreignId('primary_surgeon_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('assistant_surgeon_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('anesthetist_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('consent_id')->nullable()->constrained('patient_consents')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'surgery_number']);
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index('patient_id');
            $table->index(['theatre_id', 'scheduled_start_at', 'scheduled_end_at'], 'ot_surgeries_theatre_schedule_idx');
            $table->index(['primary_surgeon_id', 'scheduled_start_at']);
            $table->index(['anesthetist_id', 'scheduled_start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_surgeries');
    }
};
