<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Score DEFINITIONS live in SettingsService JSON (icu.scoring_definitions) — only calculated
     * RESULTS are persisted here, never overwritten when scoring rules change (spec §25).
     */
    public function up(): void
    {
        Schema::create('icu_clinical_score_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->string('score_code');
            $table->unsignedInteger('score_version')->default(1);
            $table->json('input_values');
            $table->decimal('result_value', 8, 2);
            $table->string('interpretation')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['episode_id', 'score_code', 'calculated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_clinical_score_results');
    }
};
