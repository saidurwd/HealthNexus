<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_requests', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_number', 40);
            $table->string('source_module', 20)->default('opd'); // opd | ed | ipd | icu | ot
            $table->string('component', 20);
            $table->unsignedSmallInteger('units_requested')->default(1);
            $table->string('priority', 10)->default('routine'); // routine | urgent | emergency | massive
            $table->string('indication', 300)->nullable();
            $table->string('status', 20)->default('requested'); // requested | sample_received | ready | partially_issued | issued | completed | cancelled | rejected
            $table->boolean('replacement_donor_offered')->default(false); // P-23: family offers donors
            $table->string('cancel_reason', 200)->nullable();
            $table->timestamp('required_by')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->timestamps();

            $table->unique(['organization_id', 'request_number']);
            $table->index(['organization_id', 'status', 'priority']);
            $table->index(['patient_id', 'requested_at']);
        });

        Schema::create('blood_samples', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('request_id')->constrained('blood_requests')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('abo', 3)->nullable();
            $table->string('rh', 10)->nullable();
            $table->string('status', 20)->default('received'); // received | provisional | accepted | rejected
            $table->string('rejection_reason', 30)->nullable();
            $table->unsignedSmallInteger('sequence')->default(1); // 1 = first sample, 2 = confirming sample
            $table->timestamp('collected_at');
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['request_id', 'status']);
            $table->index(['patient_id', 'collected_at']);
        });

        Schema::create('blood_crossmatches', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('request_id')->constrained('blood_requests')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('blood_units')->cascadeOnDelete();
            $table->foreignId('sample_id')->nullable()->constrained('blood_samples')->nullOnDelete();
            $table->string('method', 20)->default('electronic'); // electronic | immediate_spin | full | retrospective
            $table->string('result', 15); // compatible | incompatible
            $table->string('reason', 200)->nullable();
            $table->timestamp('performed_at');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['request_id', 'unit_id']);
        });

        Schema::create('blood_reservations', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('request_id')->constrained('blood_requests')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('blood_units')->cascadeOnDelete();
            $table->foreignId('crossmatch_id')->nullable()->constrained('blood_crossmatches')->nullOnDelete();
            $table->string('status', 15)->default('active'); // active | released | expired | issued
            $table->timestamp('reserved_until');
            $table->timestamp('released_at')->nullable();
            $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'reserved_until']);
            $table->index(['unit_id', 'status']);
        });

        Schema::create('blood_issues', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('request_id')->constrained('blood_requests')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('blood_units')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('issue_number', 40);
            $table->string('status', 15)->default('issued'); // issued | transfusing | transfused | returned | discarded
            $table->boolean('is_emergency')->default(false);
            $table->boolean('crossmatched')->default(true); // false = emergency release, retrospective crossmatch owed
            $table->string('emergency_reason', 300)->nullable();
            $table->string('authorised_by', 100)->nullable();
            $table->string('issued_to', 100)->nullable(); // person collecting the unit
            $table->decimal('issue_temp_c', 4, 1)->nullable();
            $table->timestamp('issued_at');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->unsignedSmallInteger('minutes_out')->nullable();
            $table->decimal('return_temp_c', 4, 1)->nullable();
            $table->string('return_reason', 200)->nullable();
            $table->boolean('return_accepted')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'issue_number']);
            $table->index(['unit_id', 'status']);
            $table->index(['patient_id']);
        });

        Schema::create('blood_transfusions', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issue_id')->constrained('blood_issues')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('in_progress'); // in_progress | completed | stopped_reaction | stopped_other
            $table->foreignId('verified_by_1')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by_2')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('verification_override')->default(false);
            $table->string('override_reason', 300)->nullable();
            $table->unsignedSmallInteger('volume_given_ml')->nullable();
            $table->string('stop_reason', 300)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->unique('issue_id');
            $table->index(['patient_id', 'started_at']);
        });

        Schema::create('blood_transfusion_reactions', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transfusion_id')->constrained('blood_transfusions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('reaction_type', 30);
            $table->string('severity', 10);
            $table->json('symptoms')->nullable();
            $table->string('actions_taken', 500)->nullable();
            $table->timestamp('detected_at');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('blood_incidents', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('blood_units')->nullOnDelete();
            $table->string('incident_type', 40); // transfusion_reaction | near_miss | verification_override | emergency_release | wrong_sample | temperature_excursion | recall | other
            $table->string('severity', 10)->default('moderate');
            $table->string('description', 1000);
            $table->string('ref_type', 60)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('status', 15)->default('open'); // open | investigating | closed
            $table->string('resolution', 1000)->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'occurred_at']);
        });

        Schema::create('blood_recalls', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donor_id')->nullable()->constrained('blood_donors')->nullOnDelete();
            $table->foreignId('donation_id')->nullable()->constrained('blood_donations')->nullOnDelete();
            $table->string('reason', 500);
            $table->unsignedSmallInteger('units_blocked')->default(0);
            $table->unsignedSmallInteger('recipients_found')->default(0);
            $table->json('recipient_patient_ids')->nullable();
            $table->timestamp('initiated_at');
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('blood_discards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('blood_units')->cascadeOnDelete();
            $table->string('component', 20);
            $table->string('reason', 30);
            $table->string('note', 300)->nullable();
            $table->timestamp('discarded_at');
            $table->foreignId('discarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('unit_id');
            $table->index(['organization_id', 'discarded_at']);
        });
    }

    public function down(): void
    {
        foreach (['blood_discards', 'blood_recalls', 'blood_incidents', 'blood_transfusion_reactions', 'blood_transfusions', 'blood_issues', 'blood_reservations', 'blood_crossmatches', 'blood_samples', 'blood_requests'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
