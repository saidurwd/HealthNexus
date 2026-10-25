<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Folds the spec's separate "intraoperative documentation" and "operative note" tables into
     * one (decision #13) — their field lists are near-identical.
     */
    public function up(): void
    {
        Schema::create('ot_operative_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('ot_surgeries')->cascadeOnDelete();
            $table->text('preoperative_diagnosis')->nullable();
            $table->text('postoperative_diagnosis')->nullable();
            $table->string('anesthesia_type')->nullable();
            $table->string('position')->nullable();
            $table->text('findings')->nullable();
            $table->text('technique_summary')->nullable();
            $table->string('estimated_blood_loss')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('documented_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique('surgery_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_operative_notes');
    }
};
