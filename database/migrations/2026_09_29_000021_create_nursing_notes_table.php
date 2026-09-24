<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Row-level lock, independent of Encounter.locked_at (decision #3) — an IPD encounter stays
     * open for the whole multi-day stay, so a nursing note finalizes itself via status/signed_at
     * rather than waiting for the encounter to complete.
     */
    public function up(): void
    {
        Schema::create('nursing_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('nursing_episodes')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('note_type')->default('narrative');
            $table->text('content');
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['episode_id', 'note_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_notes');
    }
};
