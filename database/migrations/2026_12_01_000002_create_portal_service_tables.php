<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 17, part 2: result release, refill requests, online payments and settlement, messaging, service requests (S04, S05, S06, S09). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_release_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type', 14); // default | lab_category | radiology
            $table->string('scope_key', 60)->default('all');
            $table->string('mode', 12); // immediate | after_review | delayed | never
            $table->unsignedSmallInteger('delay_hours')->default(0);
            $table->boolean('sensitive')->default(false);
            $table->string('note', 200)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'scope_type', 'scope_key'], 'portal_policy_unique');
        });

        // One row per lab report / radiology report: the decision whether the patient may see it. The result itself stays where it is.
        Schema::create('portal_result_releases', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 20); // lab_report | radiology_report
            $table->unsignedBigInteger('source_id');
            $table->string('title', 160)->nullable();
            $table->string('status', 14)->default('pending'); // pending | released | withheld | held_critical | never
            $table->string('mode', 12); // mode applied when the row was made
            $table->boolean('critical')->default(false);
            $table->timestamp('due_at')->nullable(); // delayed mode: earliest automatic release
            $table->timestamp('released_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 200)->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
            $table->index(['patient_id', 'status']);
            $table->index(['organization_id', 'status', 'due_at']);
        });

        Schema::create('portal_refill_requests', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('patient_portal_accounts')->cascadeOnDelete();
            $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->string('note', 300)->nullable();
            $table->string('status', 12)->default('requested'); // requested | approved | declined | needs_visit | cancelled
            $table->string('idempotency_key', 64)->unique();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('decision_note', 300)->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('portal_payments', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('patient_portal_accounts')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('billing_invoices')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('BDT');
            $table->string('gateway', 20);
            $table->string('method', 12);
            $table->string('status', 12)->default('initiated'); // initiated | pending | succeeded | failed | cancelled | expired
            $table->string('idempotency_key', 64)->unique();
            $table->string('gateway_ref', 80)->nullable()->index();
            $table->text('redirect_url')->nullable();
            $table->string('failure_reason', 200)->nullable();
            $table->unsignedBigInteger('billing_payment_id')->nullable()->index();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['invoice_id', 'status']);
        });

        Schema::create('portal_settlements', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 20);
            $table->string('file_name', 150)->nullable();
            $table->date('period_from');
            $table->date('period_to');
            $table->unsignedInteger('line_count')->default(0);
            $table->unsignedInteger('matched')->default(0);
            $table->unsignedInteger('differences')->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('fees', 14, 2)->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('portal_settlement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('portal_settlements')->cascadeOnDelete();
            $table->string('gateway_ref', 80);
            $table->decimal('amount', 12, 2);
            $table->decimal('fee', 12, 2)->default(0);
            $table->date('paid_on')->nullable();
            $table->string('result', 18); // matched | amount_differs | unknown_payment | not_succeeded
            $table->foreignId('portal_payment_id')->nullable()->constrained('portal_payments')->nullOnDelete();
            $table->timestamps();

            $table->index('gateway_ref');
        });

        Schema::create('portal_threads', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('patient_portal_accounts')->cascadeOnDelete();
            $table->string('subject', 160);
            $table->string('category', 14)->default('general');
            $table->string('status', 10)->default('open'); // open | answered | closed
            $table->boolean('urgent')->default(false);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reply_due_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'urgent']);
            $table->index(['patient_id', 'last_message_at']);
        });

        Schema::create('portal_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('portal_threads')->cascadeOnDelete();
            $table->string('sender', 8); // patient | staff | system
            $table->foreignId('account_id')->nullable()->constrained('patient_portal_accounts')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('portal_service_requests', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('patient_portal_accounts')->cascadeOnDelete();
            $table->string('kind', 20);
            $table->text('details')->nullable();
            $table->string('status', 12)->default('requested'); // requested | in_progress | done | rejected
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_on')->nullable();
            $table->string('resolution', 300)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['portal_service_requests', 'portal_messages', 'portal_threads', 'portal_settlement_lines', 'portal_settlements', 'portal_payments', 'portal_refill_requests', 'portal_result_releases', 'portal_release_policies'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
