<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipd_discharge_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('discharge_type')->default('routine');
            $table->date('planned_date')->nullable();
            $table->text('reason')->nullable();
            $table->text('discharge_diagnosis')->nullable();
            $table->foreignId('disposition_id')->nullable()->constrained('ipd_discharge_dispositions')->nullOnDelete();
            $table->text('instructions')->nullable();
            $table->boolean('follow_up_required')->default(false);
            $table->foreignId('follow_up_provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->date('follow_up_date')->nullable();
            $table->string('status')->default('requested');
            $table->timestamp('clinical_cleared_at')->nullable();
            $table->foreignId('clinical_cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('billing_cleared_at')->nullable();
            $table->foreignId('billing_cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('pharmacy_cleared_at')->nullable();
            $table->foreignId('pharmacy_cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index('admission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_discharge_requests');
    }
};
