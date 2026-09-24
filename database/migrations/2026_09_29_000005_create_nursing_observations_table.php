<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('nursing_episodes')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->string('observation_type');
            $table->string('value');
            $table->string('unit')->nullable();
            $table->decimal('reference_range_low', 10, 2)->nullable();
            $table->decimal('reference_range_high', 10, 2)->nullable();
            $table->string('status')->default('final');
            $table->timestamp('observed_at');
            $table->foreignId('observed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device_source')->nullable();
            $table->foreignId('corrects_observation_id')->nullable()->constrained('nursing_observations')->nullOnDelete();
            $table->timestamps();

            $table->index(['episode_id', 'observation_type', 'observed_at'], 'nursing_obs_episode_type_observed_idx');
            $table->index(['patient_id', 'observed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_observations');
    }
};
