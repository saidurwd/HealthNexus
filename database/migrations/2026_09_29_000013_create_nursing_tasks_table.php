<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('nursing_episodes')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('care_plan_id')->nullable()->constrained('nursing_care_plans')->nullOnDelete();
            $table->foreignId('intervention_id')->nullable()->constrained('nursing_care_plan_interventions')->nullOnDelete();
            $table->string('task_type');
            $table->timestamp('due_at');
            $table->string('priority')->default('routine');
            $table->foreignId('assigned_nurse_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('outcome')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['episode_id', 'status', 'due_at']);
            $table->index(['assigned_nurse_id', 'status']);
            $table->index(['admission_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_tasks');
    }
};
