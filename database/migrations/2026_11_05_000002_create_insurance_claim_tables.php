<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_preauths', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('policy_id')->constrained('billing_insurance_policies')->cascadeOnDelete();
            $table->foreignId('payer_id')->constrained('insurance_payers')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_number', 40);
            $table->string('payer_reference', 80)->nullable();
            $table->string('service_description', 300);
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('authorized_amount', 12, 2)->default(0);
            $table->decimal('consumed_amount', 12, 2)->default(0);
            $table->string('status', 15)->default('requested'); // requested | approved | partial | rejected | expired | cancelled
            $table->string('decision_note', 300)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamp('requested_at');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'request_number']);
            $table->index(['policy_id', 'status']);
        });

        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payer_id')->constrained('insurance_payers')->cascadeOnDelete();
            $table->foreignId('policy_id')->nullable()->constrained('billing_insurance_policies')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('insurance_plans')->nullOnDelete();
            $table->foreignId('corporate_id')->nullable()->constrained('billing_corporates')->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->constrained('billing_invoices')->cascadeOnDelete();
            $table->foreignId('preauth_id')->nullable()->constrained('insurance_preauths')->nullOnDelete();
            $table->foreignId('parent_claim_id')->nullable()->constrained('insurance_claims')->nullOnDelete();
            $table->string('claim_number', 40);
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('claim_type', 15)->default('outpatient'); // outpatient | inpatient | emergency
            $table->string('status', 20)->default('draft');
            $table->date('service_date');
            $table->json('diagnoses')->nullable(); // [{code, system, description}]
            $table->json('procedures')->nullable();
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('non_covered_amount', 12, 2)->default(0);
            $table->decimal('deductible_amount', 12, 2)->default(0);
            $table->decimal('copay_amount', 12, 2)->default(0);
            $table->decimal('payer_share', 12, 2)->default(0);
            $table->decimal('patient_share', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('adjusted_amount', 12, 2)->default(0);
            $table->json('scrub_errors')->nullable();
            $table->string('payer_reference', 80)->nullable();
            $table->date('filing_deadline')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'claim_number']);
            $table->index(['organization_id', 'status', 'payer_id']);
            $table->index(['invoice_id']);
            $table->index(['payer_id', 'submitted_at']);
        });

        Schema::create('insurance_claim_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('claim_id')->constrained('insurance_claims')->cascadeOnDelete();
            $table->foreignId('invoice_item_id')->nullable()->constrained('billing_invoice_items')->nullOnDelete();
            $table->foreignId('billing_item_id')->nullable()->constrained('billing_items')->nullOnDelete();
            $table->string('item_code', 40)->nullable();
            $table->string('description', 200);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('contract_amount', 12, 2); // after the agreed rate; never above gross
            $table->decimal('non_covered_amount', 12, 2)->default(0);
            $table->decimal('deductible_amount', 12, 2)->default(0);
            $table->decimal('copay_amount', 12, 2)->default(0);
            $table->decimal('payer_share', 12, 2)->default(0);
            $table->decimal('patient_share', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('note', 200)->nullable(); // why: excluded / capped / deductible / co-pay rule
            $table->timestamps();

            $table->index('claim_id');
        });

        // Append-only timeline of the claim.
        Schema::create('insurance_claim_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('claim_id')->constrained('insurance_claims')->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->string('note', 500)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['claim_id', 'occurred_at']);
        });

        Schema::create('insurance_claim_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('claim_id')->constrained('insurance_claims')->cascadeOnDelete();
            $table->string('document_type', 40);
            $table->boolean('required')->default(false);
            $table->boolean('received')->default(false);
            $table->unsignedBigInteger('file_id')->nullable();
            $table->string('note', 200)->nullable();
            $table->timestamps();

            $table->unique(['claim_id', 'document_type']);
        });

        Schema::create('insurance_submissions', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payer_id')->constrained('insurance_payers')->cascadeOnDelete();
            $table->string('batch_number', 40);
            $table->string('method', 20);
            $table->unsignedSmallInteger('claim_count')->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('file_path', 255)->nullable();
            $table->string('payer_reference', 80)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'batch_number']);
        });

        Schema::create('insurance_submission_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('insurance_submissions')->cascadeOnDelete();
            $table->foreignId('claim_id')->constrained('insurance_claims')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->unique(['submission_id', 'claim_id']);
        });

        // Queries, rejections and denials from the payer, and the appeals against denials.
        Schema::create('insurance_claim_queries', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('claim_id')->constrained('insurance_claims')->cascadeOnDelete();
            $table->string('kind', 12); // query | rejection | denial
            $table->string('reason_code', 10)->nullable();
            $table->string('reason_text', 500);
            $table->string('status', 12)->default('open'); // open | answered | closed
            $table->timestamp('due_at')->nullable();
            $table->string('response', 1000)->nullable();
            $table->timestamp('raised_at');
            $table->timestamp('answered_at')->nullable();
            $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['claim_id', 'status']);
            $table->index(['kind', 'reason_code']);
        });

        Schema::create('insurance_appeals', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('claim_id')->constrained('insurance_claims')->cascadeOnDelete();
            $table->foreignId('query_id')->nullable()->constrained('insurance_claim_queries')->nullOnDelete();
            $table->string('grounds', 1000);
            $table->string('status', 12)->default('filed'); // filed | upheld | overturned
            $table->string('outcome_note', 500)->nullable();
            $table->timestamp('filed_at');
            $table->foreignId('filed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['insurance_appeals', 'insurance_claim_queries', 'insurance_submission_claims', 'insurance_submissions', 'insurance_claim_documents', 'insurance_claim_events', 'insurance_claim_lines', 'insurance_claims', 'insurance_preauths'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
