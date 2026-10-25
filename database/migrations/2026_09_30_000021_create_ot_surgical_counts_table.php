<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Discrepancies are recorded, never silently corrected (spec §35) — append-only.
     */
    public function up(): void
    {
        Schema::create('ot_surgical_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('ot_surgeries')->cascadeOnDelete();
            $table->string('count_type');
            $table->string('stage')->default('initial');
            $table->string('count_value');
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('counted_at');
            $table->boolean('discrepancy')->default(false);
            $table->text('discrepancy_notes')->nullable();
            $table->timestamps();

            $table->index(['surgery_id', 'count_type', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_surgical_counts');
    }
};
