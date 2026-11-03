<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only ledger consolidating the spec's icu_episode_status_history + icu_transfer_requests
     * + icu_transfer_records into one table with an event_type discriminator — mirrors OT's
     * ot_surgery_schedule_events consolidation from Phase 10.
     */
    public function up(): void
    {
        Schema::create('icu_episode_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->foreignId('from_bed_id')->nullable()->constrained('ipd_beds')->nullOnDelete();
            $table->foreignId('to_bed_id')->nullable()->constrained('ipd_beds')->nullOnDelete();
            $table->string('destination')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['episode_id', 'event_type']);
            $table->index(['organization_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_episode_events');
    }
};
