<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ack fields folded directly onto the alert row, mirroring LabCriticalResultAlert — not a
     * separate acknowledgements table (Phase 11 plan decision #9).
     */
    public function up(): void
    {
        Schema::create('icu_critical_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->string('rule');
            $table->string('severity')->default('moderate');
            $table->string('triggered_value')->nullable();
            $table->string('expected_range')->nullable();
            $table->nullableMorphs('source');
            $table->timestamp('triggered_at');
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('action')->nullable();
            $table->string('escalation_status')->default('none');
            $table->timestamps();

            $table->index(['episode_id', 'severity', 'acknowledged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_critical_alerts');
    }
};
