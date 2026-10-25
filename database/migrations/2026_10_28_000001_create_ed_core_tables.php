<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Emergency Department core (Phase 12, S01–S05). The ED never owns a patient or an encounter: it holds the ED visit context only. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ed_zones', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('zone_type', 20);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'branch_id', 'code'], 'uq_ed_zones_code');
        });

        Schema::create('ed_bays', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained('ed_zones')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name')->nullable();
            $table->string('status', 12)->default('available');   // available, occupied, cleaning, blocked
            // One visit per bay and one bay per visit — the unique index is the last line of defence behind the row lock.
            $table->unsignedBigInteger('current_visit_id')->nullable()->unique('uq_ed_bays_current_visit');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['zone_id', 'code'], 'uq_ed_bays_code');
        });

        Schema::create('ed_visits', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visit_number', 30);
            $table->timestamp('arrival_at');
            $table->string('arrival_mode', 20)->default('walk_in');
            $table->text('chief_complaint')->nullable();
            $table->string('status', 24)->default('awaiting_triage');
            $table->unsignedTinyInteger('triage_level')->nullable();
            $table->timestamp('triage_due_at')->nullable();
            $table->timestamp('triaged_at')->nullable();
            $table->timestamp('doctor_due_at')->nullable();
            $table->timestamp('retriage_due_at')->nullable();
            $table->timestamp('doctor_seen_at')->nullable();
            $table->foreignId('zone_id')->nullable()->constrained('ed_zones')->nullOnDelete();
            $table->unsignedBigInteger('bay_id')->nullable();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('nurse_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_unknown_patient')->default(false);
            $table->boolean('is_mass_casualty')->default(false);
            $table->string('disposition_type', 30)->nullable();
            $table->timestamp('dispositioned_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'visit_number'], 'uq_ed_visits_number');
            $table->index(['organization_id', 'branch_id', 'status', 'arrival_at'], 'idx_ed_visits_board');
            $table->index(['patient_id', 'arrival_at'], 'idx_ed_visits_patient');
        });

        Schema::create('ed_triage_records', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained('ed_visits')->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->boolean('is_retriage')->default(false);
            $table->text('complaint')->nullable();
            $table->json('discriminators')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('observation_set_id')->nullable()->constrained('clinical_observation_sets')->nullOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at');
            $table->timestamps();

            $table->index(['visit_id', 'assessed_at']);
        });

        // Append-only history of where a patient was: who held which bay from when to when.
        Schema::create('ed_bay_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('ed_visits')->cascadeOnDelete();
            $table->foreignId('bay_id')->constrained('ed_bays')->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['visit_id', 'assigned_at']);
        });

        Schema::create('ed_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('ed_visits')->cascadeOnDelete();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->string('note')->nullable();

            $table->index(['visit_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ed_status_history');
        Schema::dropIfExists('ed_bay_assignments');
        Schema::dropIfExists('ed_triage_records');
        Schema::dropIfExists('ed_visits');
        Schema::dropIfExists('ed_bays');
        Schema::dropIfExists('ed_zones');
    }
};
