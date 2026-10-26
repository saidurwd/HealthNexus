<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_storage_locations', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('kind', 20); // fridge | freezer | agitator | quarantine
            $table->decimal('min_temp_c', 5, 1);
            $table->decimal('max_temp_c', 5, 1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('blood_temperature_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('blood_storage_locations')->cascadeOnDelete();
            $table->decimal('celsius', 5, 1);
            $table->string('source', 30)->default('manual'); // manual | device
            $table->boolean('is_excursion')->default(false);
            $table->timestamp('recorded_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['location_id', 'recorded_at']);
        });

        Schema::create('blood_donors', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete(); // the donor is a person in the MPI
            $table->string('donor_number', 30);
            $table->string('abo', 3)->nullable();
            $table->string('rh', 10)->nullable();
            $table->string('status', 20)->default('eligible'); // eligible | deferred | permanently_deferred | inactive
            $table->date('deferred_until')->nullable();
            $table->string('deferral_reason', 60)->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->foreignId('consent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_donation_at')->nullable();
            $table->unsignedSmallInteger('donation_count')->default(0);
            $table->boolean('is_replacement')->default(false); // gave for a named patient (P-23)
            $table->timestamps();

            $table->unique(['organization_id', 'donor_number']);
            $table->unique(['organization_id', 'patient_id']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('blood_donor_screenings', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donor_id')->constrained('blood_donors')->cascadeOnDelete();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->decimal('hemoglobin', 4, 1)->nullable();
            $table->unsignedSmallInteger('systolic')->nullable();
            $table->unsignedSmallInteger('diastolic')->nullable();
            $table->unsignedSmallInteger('pulse')->nullable();
            $table->decimal('temperature_c', 4, 1)->nullable();
            $table->json('questionnaire')->nullable();
            $table->string('outcome', 20); // eligible | deferred | rejected
            $table->json('failed_rules')->nullable();
            $table->string('deferral_reason', 60)->nullable();
            $table->date('deferred_until')->nullable();
            $table->timestamp('screened_at');
            $table->foreignId('screened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['donor_id', 'screened_at']);
        });

        Schema::create('blood_donations', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('donor_id')->constrained('blood_donors')->cascadeOnDelete();
            $table->foreignId('screening_id')->constrained('blood_donor_screenings')->cascadeOnDelete();
            $table->string('donation_number', 40);
            $table->string('bag_lot', 40)->nullable();
            $table->unsignedSmallInteger('volume_ml')->nullable();
            $table->string('status', 20)->default('collecting'); // collecting | collected | aborted
            $table->string('abort_reason', 200)->nullable();
            $table->unsignedBigInteger('replacement_request_id')->nullable(); // set when given for a patient's request
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete(); // the "blood donation" encounter that carries the lab order
            $table->foreignId('clinical_order_id')->nullable()->constrained('clinical_orders')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('observation_until')->nullable();
            $table->timestamp('observation_done_at')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'donation_number']);
            $table->index(['donor_id', 'collected_at']);
        });

        Schema::create('blood_donor_adverse_events', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donation_id')->constrained('blood_donations')->cascadeOnDelete();
            $table->string('event_type', 30);
            $table->string('severity', 10)->default('mild');
            $table->string('description', 500)->nullable();
            $table->string('action_taken', 500)->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('blood_units', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('donation_id')->nullable()->constrained('blood_donations')->nullOnDelete();
            $table->foreignId('parent_unit_id')->nullable()->constrained('blood_units')->nullOnDelete();
            $table->string('unit_number', 40);
            $table->string('component', 20);
            $table->string('abo', 3)->nullable();
            $table->string('rh', 10)->nullable();
            $table->unsignedSmallInteger('volume_ml');
            $table->string('status', 20)->default('quarantined'); // quarantined | available | reserved | issued | transfused | discarded | expired | blocked
            $table->string('blocked_reason', 120)->nullable();
            $table->foreignId('location_id')->nullable()->constrained('blood_storage_locations')->nullOnDelete();
            $table->boolean('is_external')->default(false); // received from another blood bank
            $table->timestamp('collected_at');
            $table->timestamp('expires_at');
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'unit_number']);
            $table->index(['organization_id', 'status', 'component', 'expires_at'], 'bu_org_status_comp_exp_idx');
            $table->index(['donation_id']);
        });

        // Append-only trail of everything that happens to a unit: the traceability record.
        Schema::create('blood_unit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('blood_units')->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->string('ref_type', 60)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('note', 500)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['unit_id', 'occurred_at']);
        });

        // One row per test per donation. The value comes from the Laboratory result (lab_result_id); this row only records the outcome used for release.
        Schema::create('blood_unit_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donation_id')->constrained('blood_donations')->cascadeOnDelete();
            $table->string('test_code', 20);
            $table->string('outcome', 20)->default('pending'); // pending | pass | fail | discordant
            $table->string('value', 40)->nullable(); // the lab's qualitative value as read, for display
            $table->unsignedBigInteger('lab_result_id')->nullable();
            $table->timestamp('resulted_at')->nullable();
            $table->timestamps();

            $table->unique(['donation_id', 'test_code']);
        });
    }

    public function down(): void
    {
        foreach (['blood_unit_tests', 'blood_unit_events', 'blood_units', 'blood_donor_adverse_events', 'blood_donations', 'blood_donor_screenings', 'blood_donors', 'blood_temperature_logs', 'blood_storage_locations'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
