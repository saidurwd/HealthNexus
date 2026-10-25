<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only; hourly/shift/daily/cumulative balance is computed via aggregation query,
     * no stored summary row (Phase 11 plan decision #15).
     */
    public function up(): void
    {
        Schema::create('icu_fluid_balance_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->string('type');
            $table->string('category');
            $table->decimal('volume_ml', 10, 2);
            $table->timestamp('recorded_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['episode_id', 'type', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_fluid_balance_entries');
    }
};
