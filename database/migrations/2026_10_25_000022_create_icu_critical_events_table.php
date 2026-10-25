<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consolidates the spec's icu_critical_events + icu_code_blue_events into one table with an
     * event_type discriminator and nullable Code-Blue-specific fields (Phase 11 plan decision #16).
     */
    public function up(): void
    {
        Schema::create('icu_critical_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('location')->nullable();
            $table->timestamp('event_time');
            $table->timestamp('recognition_time')->nullable();
            $table->timestamp('response_time')->nullable();
            $table->text('initial_condition')->nullable();
            $table->text('participants')->nullable();
            $table->text('interventions')->nullable();
            $table->text('medications')->nullable();
            $table->text('procedures')->nullable();
            $table->boolean('defibrillation')->nullable();
            $table->text('airway_management')->nullable();
            $table->boolean('rosc')->nullable();
            $table->string('outcome')->nullable();
            $table->text('post_event_plan')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->index(['episode_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_critical_events');
    }
};
