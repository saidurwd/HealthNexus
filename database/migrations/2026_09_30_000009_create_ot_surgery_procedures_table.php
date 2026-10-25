<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks primary/secondary/additional/unplanned/aborted procedures per surgery (spec §8) —
     * the surgeon may document more or different procedures than scheduled; the scheduled
     * procedure on ot_surgeries.primary_procedure_id is never silently overwritten.
     */
    public function up(): void
    {
        Schema::create('ot_surgery_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('ot_surgeries')->cascadeOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('ot_procedures')->nullOnDelete();
            $table->string('role')->default('primary');
            $table->unsignedInteger('sequence')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['surgery_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_surgery_procedures');
    }
};
