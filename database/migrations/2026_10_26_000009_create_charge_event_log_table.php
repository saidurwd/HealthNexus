<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Charge Event Contract log (guideline 11.2, H-05): what every module announced to Billing and what Billing did.
 * It never holds prices. One row per idempotency key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_event_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('idempotency_key');                         // MODULE:visit_ref:service_code:source_event_id
            $table->string('module', 10);
            $table->string('visit_ref', 40)->nullable();
            $table->string('service_code', 100);
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('admission_id')->nullable();
            $table->decimal('quantity', 18, 4)->default(1);
            $table->boolean('is_chargeable')->default(true);
            $table->string('no_charge_reason')->nullable();
            $table->string('billing_party_hint', 12)->nullable();     // patient, insurance, corporate
            $table->string('source_type', 100)->nullable();
            $table->string('source_id', 40)->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('outcome', 30);                             // accepted, rejected, not_chargeable, reversed, reversal_needs_credit_note
            $table->string('outcome_message')->nullable();
            $table->foreignId('billing_charge_id')->nullable()->constrained('billing_charges')->nullOnDelete();
            $table->unsignedSmallInteger('publish_count')->default(1);  // >1 means the same event arrived again (no double charge)
            $table->timestamps();

            $table->unique(['organization_id', 'idempotency_key'], 'uq_charge_event_log_key');
            $table->index(['organization_id', 'outcome', 'created_at'], 'idx_charge_event_log_outcome');
            $table->index(['source_type', 'source_id'], 'idx_charge_event_log_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_event_log');
    }
};
