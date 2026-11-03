<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Richer than Phase 9's nursing_iv_infusions (no titration history, concentration, or
     * prescriber field there) — built fresh per Phase 11 plan decision #14 rather than altering
     * Nursing's shipped table. Current rate is always the latest icu_infusion_titrations row.
     */
    public function up(): void
    {
        Schema::create('icu_infusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->string('medication');
            $table->string('concentration')->nullable();
            $table->decimal('initial_rate', 10, 3)->nullable();
            $table->string('rate_unit')->nullable();
            $table->string('route')->nullable();
            $table->decimal('target', 10, 3)->nullable();
            $table->foreignId('prescriber_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('icu_devices')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            $table->string('status')->default('running');
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('stopped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['episode_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_infusions');
    }
};
