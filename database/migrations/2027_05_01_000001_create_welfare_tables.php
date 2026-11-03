<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Patient welfare (Phase 26, Bangladesh pack): money given to the hospital for patients who cannot pay (zakat, donations, government or CSR grants, the hospital's own charity fund) and the decisions that spend it.
 * A fund's balance is never stored: it is the sum of its entries, and entries never change. A person is assessed, a committee approves a grant against a fund, and the waiver reaches the bill only as a Billing discount
 * adjustment that Billing's own approver accepts; the fund is charged when that adjustment is approved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_funds', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('kind', 12); // zakat | donation | government | csr | hospital
            $table->string('description', 300)->nullable();
            $table->decimal('per_grant_cap', 12, 2)->nullable();
            $table->decimal('low_balance_alert', 12, 2)->default(0);
            $table->string('status', 8)->default('active'); // active | closed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'name'], 'wf_funds_org_name_uq');
        });

        Schema::create('wf_donors', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('kind', 14)->default('individual'); // individual | company | organisation
            $table->string('phone', 30)->nullable();
            $table->boolean('anonymous')->default(false);
            $table->string('note', 200)->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'name'], 'wf_donors_org_name_idx');
        });

        Schema::create('wf_assessments', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->unsignedSmallInteger('household_size')->default(1);
            $table->string('occupation', 80)->nullable();
            $table->json('factors')->nullable(); // keys from config('welfare.factors')
            $table->decimal('per_head', 12, 2)->nullable();
            $table->unsignedSmallInteger('score')->default(0);
            $table->string('category', 10)->default('none'); // free | subsidised | none
            $table->unsignedSmallInteger('suggested_percent')->default(0);
            $table->string('evidence_note', 500)->nullable();
            $table->string('status', 10)->default('draft'); // draft | submitted | approved | rejected | expired
            $table->date('valid_until')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note', 300)->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'number'], 'wf_assess_org_number_uq');
            $table->index(['organization_id', 'status'], 'wf_assess_status_idx');
            $table->index(['patient_id', 'status'], 'wf_assess_patient_idx');
        });

        Schema::create('wf_grants', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30);
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained('wf_assessments')->cascadeOnDelete();
            $table->foreignId('fund_id')->constrained('wf_funds')->cascadeOnDelete();
            $table->decimal('cap_amount', 12, 2);
            $table->unsignedSmallInteger('percent');
            $table->decimal('spent_amount', 12, 2)->default(0);
            $table->date('valid_from');
            $table->date('valid_to');
            $table->string('purpose', 200)->nullable();
            $table->string('status', 10)->default('requested'); // requested | approved | rejected | used | expired | cancelled
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('decision_note', 300)->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'number'], 'wf_grants_org_number_uq');
            $table->index(['organization_id', 'status'], 'wf_grants_status_idx');
            $table->index(['patient_id', 'status'], 'wf_grants_patient_idx');
            $table->index(['fund_id', 'status'], 'wf_grants_fund_idx');
        });

        Schema::create('wf_applications', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grant_id')->constrained('wf_grants')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('billing_invoices')->cascadeOnDelete();
            $table->foreignId('adjustment_id')->nullable()->constrained('billing_adjustments')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 10)->default('requested'); // requested | applied | rejected | cancelled
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->string('note', 300)->nullable();
            $table->timestamps();
            $table->unique('adjustment_id', 'wf_apps_adjustment_uq');
            $table->index(['organization_id', 'status'], 'wf_apps_status_idx');
            $table->index(['grant_id', 'status'], 'wf_apps_grant_idx');
            $table->index('invoice_id', 'wf_apps_invoice_idx');
        });

        Schema::create('wf_fund_entries', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fund_id')->constrained('wf_funds')->cascadeOnDelete();
            $table->string('entry_type', 12); // donation | disbursement | reversal
            $table->decimal('amount', 14, 2); // + into the fund, − out of it
            $table->foreignId('donor_id')->nullable()->constrained('wf_donors')->nullOnDelete();
            $table->foreignId('grant_id')->nullable()->constrained('wf_grants')->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('wf_applications')->nullOnDelete();
            $table->string('receipt_no', 30)->nullable();
            $table->string('reference', 80)->nullable();
            $table->string('note', 300)->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->index(['fund_id', 'occurred_at'], 'wf_entries_fund_idx');
            $table->unique(['application_id', 'entry_type'], 'wf_entries_app_type_uq');
            $table->unique(['organization_id', 'receipt_no'], 'wf_entries_receipt_uq');
        });

        $sqlite = DB::getDriverName() === 'sqlite';
        foreach (['update' => 'UPDATE', 'delete' => 'DELETE'] as $k => $op) {
            DB::unprepared($sqlite
                ? "CREATE TRIGGER wf_fund_entries_no_$k BEFORE $op ON wf_fund_entries BEGIN SELECT RAISE(ABORT, 'a fund entry is never changed or deleted: add a reversal'); END"
                : "CREATE TRIGGER wf_fund_entries_no_$k BEFORE $op ON wf_fund_entries FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'a fund entry is never changed or deleted: add a reversal'; END");
        }
    }

    public function down(): void
    {
        foreach (['wf_fund_entries_no_update', 'wf_fund_entries_no_delete'] as $t) {
            DB::unprepared("DROP TRIGGER IF EXISTS $t");
        }
        foreach (['wf_fund_entries', 'wf_applications', 'wf_grants', 'wf_assessments', 'wf_donors', 'wf_funds'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
