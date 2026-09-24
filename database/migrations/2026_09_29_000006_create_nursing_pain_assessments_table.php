<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_pain_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('nursing_episodes')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('observation_id')->nullable()->constrained('nursing_observations')->nullOnDelete();
            $table->string('scale_type')->default('numeric');
            $table->string('score');
            $table->string('location')->nullable();
            $table->string('character')->nullable();
            $table->string('onset')->nullable();
            $table->string('duration')->nullable();
            $table->text('aggravating_factors')->nullable();
            $table->text('relieving_factors')->nullable();
            $table->text('intervention')->nullable();
            $table->timestamp('reassessment_due_at')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at');
            $table->timestamps();

            $table->index(['episode_id', 'assessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_pain_assessments');
    }
};
