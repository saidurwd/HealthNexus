<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_duplicate_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // patient_id_a is always the lower id of the pair, patient_id_b the higher — enforced
            // in code so the same pair can never be inserted twice in reversed order.
            $table->foreignId('patient_id_a')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('patient_id_b')->constrained('patients')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->enum('classification', ['possible_match', 'strong_match']);
            $table->enum('status', ['pending', 'confirmed_duplicate', 'not_duplicate', 'needs_investigation', 'merged', 'rejected'])->default('pending');
            $table->json('match_reasons')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique(['patient_id_a', 'patient_id_b'], 'patient_duplicate_pair_unique');
            $table->index(['company_id', 'status'], 'patient_duplicate_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_duplicate_candidates');
    }
};
