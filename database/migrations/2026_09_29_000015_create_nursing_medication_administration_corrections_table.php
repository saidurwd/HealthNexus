<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only corrections ledger (spec §62) — a finalized administration is never edited in
     * place; correct() records the intended change here and only sets a
     * superseded_by_correction_id marker on the original row.
     */
    public function up(): void
    {
        Schema::create('nursing_medication_administration_corrections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('administration_id');
            $table->foreign('administration_id', 'nursing_mar_corrections_admin_fk')->references('id')->on('nursing_medication_administrations')->cascadeOnDelete();
            $table->string('correction_type');
            $table->text('reason');
            $table->json('previous_values');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('administration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_medication_administration_corrections');
    }
};
