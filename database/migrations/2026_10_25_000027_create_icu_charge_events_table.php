<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-day idempotency anchor for recurring bed-day/ventilator-day charges, mirroring
     * IpdBedChargeEvent exactly (Phase 11 plan decision #11) — not a parallel invoice engine.
     */
    public function up(): void
    {
        Schema::create('icu_charge_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->string('charge_type');
            $table->date('charge_date');
            $table->timestamps();

            $table->unique(['episode_id', 'charge_type', 'charge_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_charge_events');
    }
};
