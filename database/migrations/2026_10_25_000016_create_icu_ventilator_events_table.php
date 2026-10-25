<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only; "current ventilator settings" is always the latest row for the episode —
     * never a separately-maintained mutable record (Phase 11 plan decision #13).
     */
    public function up(): void
    {
        Schema::create('icu_ventilator_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('mode')->nullable();
            $table->decimal('fio2', 5, 2)->nullable();
            $table->decimal('peep', 6, 2)->nullable();
            $table->decimal('tidal_volume', 7, 2)->nullable();
            $table->decimal('respiratory_rate', 5, 2)->nullable();
            $table->decimal('pressure_support', 6, 2)->nullable();
            $table->decimal('peak_pressure', 6, 2)->nullable();
            $table->decimal('plateau_pressure', 6, 2)->nullable();
            $table->decimal('mean_airway_pressure', 6, 2)->nullable();
            $table->string('ie_ratio')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['episode_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_ventilator_events');
    }
};
