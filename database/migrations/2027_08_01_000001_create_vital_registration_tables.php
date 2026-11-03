<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Birth notification, death certification and medico-legal cases (Phase 26, B2). A certified or issued record is never edited: it is cancelled with a reason and, if needed, written again. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vr_birth_notifications', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->foreignId('mother_patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('child_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('outcome', 12)->default('live_birth'); // live_birth | stillbirth
            $table->string('child_name', 150)->nullable();
            $table->string('sex', 14); // male | female | other | undetermined
            $table->timestamp('born_at');
            $table->string('place_of_birth', 16)->default('this_hospital'); // this_hospital | en_route | home | other
            $table->unsignedTinyInteger('plurality')->default(1); // 1 single, 2 twins, 3 triplets ...
            $table->unsignedTinyInteger('birth_order')->default(1);
            $table->unsignedSmallInteger('weight_g')->nullable();
            $table->unsignedTinyInteger('gestation_weeks')->nullable();
            $table->string('delivery_type', 16)->nullable(); // normal | caesarean | assisted | breech | other
            $table->string('father_name', 150)->nullable();
            $table->text('father_nid')->nullable(); // encrypted
            $table->string('informant_name', 150)->nullable();
            $table->string('informant_relation', 60)->nullable();
            $table->foreignId('attendant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attendant_name', 150)->nullable();
            $table->foreignId('division_id')->nullable()->constrained('geo_divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('geo_districts')->nullOnDelete();
            $table->foreignId('upazila_id')->nullable()->constrained('geo_upazilas')->nullOnDelete();
            $table->foreignId('union_id')->nullable()->constrained('geo_unions')->nullOnDelete();
            $table->string('village_area', 120)->nullable();
            $table->string('status', 12)->default('draft'); // draft | issued | registered | cancelled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete(); // the second person who checked the details
            $table->timestamp('issued_at')->nullable();
            $table->string('issued_to', 150)->nullable();
            $table->string('registrar_reference', 30)->nullable(); // the number the registrar gave (birth registration number)
            $table->timestamp('registered_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status', 'born_at'], 'vr_birth_org_status_born_idx');
        });

        Schema::create('vr_medicolegal_cases', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20); // accident | assault | burn | poisoning | sexual_offence | suspicious_death | brought_dead | other
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('brought_at');
            $table->string('brought_by', 150)->nullable();
            $table->text('description'); // restricted: only people with the medico-legal right read it
            $table->text('injuries')->nullable();
            $table->string('status', 20)->default('open'); // open | police_informed | postmortem_requested | closed
            $table->string('police_station', 120)->nullable();
            $table->string('gd_number', 40)->nullable(); // General Diary entry
            $table->string('case_number', 40)->nullable();
            $table->string('officer_name', 150)->nullable();
            $table->string('officer_contact', 60)->nullable();
            $table->timestamp('police_informed_at')->nullable();
            $table->foreignId('police_informed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('postmortem_requested_at')->nullable();
            $table->timestamp('postmortem_done_at')->nullable();
            $table->foreignId('mortuary_case_id')->nullable()->constrained('fac_mortuary_cases')->nullOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('closure_note', 255)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status'], 'vr_ml_org_status_idx');
        });

        Schema::create('vr_death_certifications', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 20)->nullable(); // ed_visit | ipd_admission
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('mortuary_case_id')->nullable()->constrained('fac_mortuary_cases')->nullOnDelete();
            $table->foreignId('medicolegal_case_id')->nullable()->constrained('vr_medicolegal_cases')->nullOnDelete();
            $table->timestamp('died_at');
            $table->string('place_of_death', 16)->default('this_hospital'); // this_hospital | en_route | home | other
            $table->string('ward_or_area', 80)->nullable();
            $table->string('manner', 24); // natural | accident | suicide | homicide | undetermined | pending_investigation
            // The medical certificate of cause of death (WHO form): Part I is a chain, a being the direct cause and each line due to the one below it.
            $table->text('cause_a');
            $table->string('interval_a', 40)->nullable();
            $table->text('cause_b')->nullable();
            $table->string('interval_b', 40)->nullable();
            $table->text('cause_c')->nullable();
            $table->string('interval_c', 40)->nullable();
            $table->text('cause_d')->nullable();
            $table->string('interval_d', 40)->nullable();
            $table->text('contributing')->nullable(); // Part II
            $table->string('underlying_icd10', 12)->nullable();
            $table->boolean('autopsy_done')->default(false);
            $table->string('pregnancy_status', 24)->nullable(); // not_applicable | pregnant | within_42_days | 43_days_to_1_year | unknown
            $table->boolean('medico_legal')->default(false);
            $table->string('status', 12)->default('draft'); // draft | certified | issued | cancelled
            $table->foreignId('certifier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('certifier_name', 150)->nullable();
            $table->string('certifier_reg_no', 40)->nullable(); // the doctor's registration number
            $table->timestamp('certified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete(); // the second person who checked who the family member is
            $table->timestamp('issued_at')->nullable();
            $table->string('issued_to', 150)->nullable();
            $table->string('issued_relation', 60)->nullable();
            $table->string('issued_id_type', 20)->nullable();
            $table->text('issued_id_number')->nullable(); // encrypted
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason', 255)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status', 'died_at'], 'vr_death_org_status_died_idx');
            $table->index(['organization_id', 'patient_id'], 'vr_death_org_patient_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vr_death_certifications');
        Schema::dropIfExists('vr_medicolegal_cases');
        Schema::dropIfExists('vr_birth_notifications');
    }
};
