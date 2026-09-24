<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot rows — content is pulled from live data at prepare-time and frozen here, so a
     * historical handover stays immutable even as the source records change later (spec §10).
     */
    public function up(): void
    {
        Schema::create('nursing_handover_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('handover_id')->constrained('nursing_handover')->cascadeOnDelete();
            $table->string('section');
            $table->text('content');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();

            $table->index(['handover_id', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_handover_items');
    }
};
