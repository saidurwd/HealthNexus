<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icu_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->string('observation_type');
            $table->string('code')->nullable();
            $table->decimal('value_numeric', 12, 4)->nullable();
            $table->text('value_text')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('reference_range_low', 10, 2)->nullable();
            $table->decimal('reference_range_high', 10, 2)->nullable();
            $table->timestamp('observed_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device_source')->nullable();
            $table->foreignId('corrects_observation_id')->nullable()->constrained('icu_observations')->nullOnDelete();
            $table->timestamps();

            $table->index(['episode_id', 'observation_type', 'observed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_observations');
    }
};
