<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('nursing_episodes')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('risk_type');
            $table->string('tool_name')->nullable();
            $table->string('score')->nullable();
            $table->string('risk_level')->nullable();
            $table->text('contributing_factors')->nullable();
            $table->text('interventions')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at');
            $table->timestamp('reassessment_due_at')->nullable();
            $table->timestamps();

            $table->index(['episode_id', 'risk_type', 'assessed_at']);
            $table->index(['admission_id', 'risk_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_risk_assessments');
    }
};
