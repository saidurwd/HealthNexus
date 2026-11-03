<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Maternal and child health (Phase 26, B4): pregnancies and antenatal care, deliveries and newborns, and the child's routine immunisation (EPI). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mch_pregnancies', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->date('lmp')->nullable(); // first day of the last period
            $table->date('edd'); // expected date of delivery
            $table->unsignedTinyInteger('gravida')->default(1); // pregnancies including this one
            $table->unsignedTinyInteger('para')->default(0); // births before this one
            $table->json('risk_factors')->nullable(); // set at booking: key list from config
            $table->string('status', 12)->default('active'); // active | delivered | ended
            $table->string('end_reason', 20)->nullable(); // miscarriage | abortion | ectopic | transferred | lost_to_follow_up
            $table->timestamp('booked_at');
            $table->foreignId('booked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status', 'edd'], 'mch_preg_org_status_edd_idx');
            $table->index(['organization_id', 'patient_id'], 'mch_preg_org_patient_idx');
        });

        Schema::create('mch_anc_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pregnancy_id')->constrained('mch_pregnancies')->cascadeOnDelete();
            $table->unsignedTinyInteger('visit_no');
            $table->timestamp('visited_at');
            $table->unsignedTinyInteger('weeks')->nullable(); // weeks of pregnancy at the visit
            $table->unsignedSmallInteger('bp_systolic')->nullable();
            $table->unsignedSmallInteger('bp_diastolic')->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->unsignedTinyInteger('fundal_height_cm')->nullable();
            $table->unsignedSmallInteger('fetal_heart_rate')->nullable();
            $table->decimal('hemoglobin', 4, 1)->nullable();
            $table->string('urine_protein', 10)->nullable(); // nil | trace | plus1 | plus2 | plus3
            $table->boolean('tt_given')->default(false); // tetanus-diphtheria dose
            $table->json('danger_signs')->nullable();
            $table->json('alerts')->nullable(); // worked out from the readings: key list
            $table->date('next_visit_on')->nullable();
            $table->string('notes', 500)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['pregnancy_id', 'visit_no']);
        });

        Schema::create('mch_deliveries', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pregnancy_id')->constrained('mch_pregnancies')->cascadeOnDelete();
            $table->timestamp('delivered_at');
            $table->unsignedTinyInteger('weeks')->nullable();
            $table->string('mode', 16); // normal | caesarean | assisted | breech
            $table->string('place', 16)->default('this_hospital');
            $table->string('attendant_name', 150)->nullable();
            $table->unsignedSmallInteger('blood_loss_ml')->nullable();
            $table->json('complications')->nullable(); // key list from config
            $table->string('mother_outcome', 12)->default('well'); // well | referred | died
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('pregnancy_id');
        });

        Schema::create('mch_newborns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_id')->constrained('mch_deliveries')->cascadeOnDelete();
            $table->unsignedTinyInteger('birth_order')->default(1);
            $table->string('outcome', 12)->default('live_birth'); // live_birth | stillbirth | neonatal_death
            $table->string('sex', 14);
            $table->unsignedSmallInteger('weight_g')->nullable();
            $table->unsignedTinyInteger('apgar_1')->nullable();
            $table->unsignedTinyInteger('apgar_5')->nullable();
            $table->boolean('breastfed_within_hour')->default(false);
            $table->boolean('vitamin_k_given')->default(false);
            $table->string('admitted_to', 12)->nullable(); // nursery | nicu | null
            $table->foreignId('child_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('birth_notification_id')->nullable()->constrained('vr_birth_notifications')->nullOnDelete();
            $table->timestamps();

            $table->unique(['delivery_id', 'birth_order']);
        });

        Schema::create('mch_immunisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('vaccine', 20); // key of the dose in config/mch.php (bcg, penta1, mr2 ...)
            $table->timestamp('given_at');
            $table->string('batch_no', 40)->nullable();
            $table->string('site', 30)->nullable();
            $table->string('status', 10)->default('given'); // given | recorded (given elsewhere, from the card)
            $table->string('note', 255)->nullable();
            $table->foreignId('given_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['patient_id', 'vaccine']);
            $table->index(['organization_id', 'vaccine', 'given_at'], 'mch_imm_org_vaccine_given_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mch_immunisations');
        Schema::dropIfExists('mch_newborns');
        Schema::dropIfExists('mch_deliveries');
        Schema::dropIfExists('mch_anc_visits');
        Schema::dropIfExists('mch_pregnancies');
    }
};
