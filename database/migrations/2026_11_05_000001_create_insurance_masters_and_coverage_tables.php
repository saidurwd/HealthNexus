<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The single payer register (P-17): insurers, TPAs, corporate accounts, government / NGO / charity schemes, hospital panels.
        Schema::create('insurance_payers', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('payer_type', 20);
            $table->foreignId('insurance_provider_id')->nullable()->constrained('billing_insurance_providers')->nullOnDelete();
            $table->foreignId('corporate_id')->nullable()->constrained('billing_corporates')->nullOnDelete();
            $table->foreignId('parent_payer_id')->nullable()->constrained('insurance_payers')->nullOnDelete(); // a TPA's insurer, a scheme's fund
            $table->string('coding_system', 10)->default('icd10');
            $table->string('submission_method', 20)->default('file_export');
            $table->unsignedSmallInteger('filing_deadline_days')->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->default(30);
            $table->decimal('preauth_required_above', 12, 2)->nullable();
            $table->string('contact_name', 100)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('status', 15)->default('active');
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('insurance_plans', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payer_id')->constrained('insurance_payers')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->decimal('copay_percent', 6, 2)->default(0);
            $table->decimal('deductible_amount', 12, 2)->default(0);
            $table->decimal('annual_limit', 12, 2)->nullable();
            $table->decimal('per_visit_limit', 12, 2)->nullable();
            $table->decimal('room_rent_limit_per_day', 12, 2)->nullable();
            $table->boolean('requires_preauth_for_admission')->default(false);
            $table->boolean('is_network_only')->default(false);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('status', 15)->default('active');
            $table->timestamps();

            $table->unique(['payer_id', 'code']);
        });

        // Coverage rules of a plan: what is covered, excluded or capped, and any co-pay that differs from the plan's.
        Schema::create('insurance_plan_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('insurance_plans')->cascadeOnDelete();
            $table->string('rule_type', 15); // exclude | cover | limit
            $table->string('match_type', 15); // item_code | category | item_type
            $table->string('match_value', 60);
            $table->decimal('copay_percent', 6, 2)->nullable();
            $table->decimal('limit_amount', 12, 2)->nullable(); // cap per claim for matching lines
            $table->boolean('requires_preauth')->default(false);
            $table->string('note', 200)->nullable();
            $table->timestamps();

            $table->index(['plan_id', 'rule_type']);
        });

        // Provider contract: the rate the payer agreed to pay for a billing item, effective-dated. Billing's price is never changed by it.
        Schema::create('insurance_contract_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payer_id')->constrained('insurance_payers')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('insurance_plans')->nullOnDelete();
            $table->foreignId('billing_item_id')->constrained('billing_items')->cascadeOnDelete();
            $table->decimal('agreed_rate', 12, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['payer_id', 'billing_item_id', 'effective_from'], 'ins_rate_payer_item_from_idx');
        });

        Schema::table('billing_insurance_policies', function (Blueprint $table) {
            $table->foreignId('payer_id')->nullable()->after('insurance_provider_id')->constrained('insurance_payers')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->after('payer_id')->constrained('insurance_plans')->nullOnDelete();
            $table->foreignId('subscriber_patient_id')->nullable()->after('patient_id')->constrained('patients')->nullOnDelete();
            $table->string('relationship_to_subscriber', 20)->default('self')->after('subscriber_patient_id');
            $table->unsignedTinyInteger('coverage_order')->default(1)->after('relationship_to_subscriber'); // 1 = primary; 2 = secondary (coordination of benefits)
            $table->decimal('annual_used', 12, 2)->default(0)->after('coverage_limit');
            $table->decimal('deductible_met', 12, 2)->default(0)->after('annual_used');
            $table->date('benefit_year_start')->nullable()->after('deductible_met');
        });

        Schema::create('insurance_policy_dependents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('policy_id')->constrained('billing_insurance_policies')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 20);
            $table->string('member_number', 60)->nullable();
            $table->timestamps();

            $table->unique(['policy_id', 'patient_id']);
        });

        Schema::create('insurance_eligibility_checks', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('policy_id')->constrained('billing_insurance_policies')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 15); // manual | adapter
            $table->string('status', 15); // eligible | not_eligible | unknown
            $table->string('note', 300)->nullable();
            $table->json('response')->nullable();
            $table->timestamp('checked_at');
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['policy_id', 'checked_at']);
            $table->index(['encounter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_eligibility_checks');
        Schema::dropIfExists('insurance_policy_dependents');
        Schema::table('billing_insurance_policies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payer_id');
            $table->dropConstrainedForeignId('plan_id');
            $table->dropConstrainedForeignId('subscriber_patient_id');
            $table->dropColumn(['coverage_order', 'relationship_to_subscriber', 'annual_used', 'deductible_met', 'benefit_year_start']);
        });
        Schema::dropIfExists('insurance_contract_rates');
        Schema::dropIfExists('insurance_plan_rules');
        Schema::dropIfExists('insurance_plans');
        Schema::dropIfExists('insurance_payers');
    }
};
