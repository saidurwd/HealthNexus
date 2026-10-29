<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 21, part 2: receivables, payables and supplier payments, bank and cash, tax, budgets, fixed assets, fee sharing (S04-S11). */
return new class extends Migration
{
    public function up(): void
    {
        // ---- receivables (S04): one open item per invoice or claim; the ageing and the statements come from here ------------------------------------
        Schema::create('fin_receivables', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('party_type', 10); // patient | corporate | payer
            $table->unsignedBigInteger('party_id');
            $table->string('source_type', 20); // invoice | claim
            $table->unsignedBigInteger('source_id');
            $table->string('document_number', 40)->nullable();
            $table->date('document_date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 18, 2);
            $table->decimal('settled', 18, 2)->default(0);
            $table->decimal('written_off', 18, 2)->default(0);
            $table->string('status', 12)->default('open'); // open | settled | written_off | cancelled
            $table->foreignId('cost_center_id')->nullable()->constrained('fin_cost_centers')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'source_type', 'source_id'], 'fin_receivables_source_unique');
            $table->index(['organization_id', 'party_type', 'party_id', 'status'], 'fin_receivables_party');
            $table->index(['organization_id', 'status', 'due_date']);
        });

        Schema::create('fin_receivable_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_id')->constrained('fin_receivables')->cascadeOnDelete();
            $table->string('kind', 12); // payment | refund | advance | writeoff | credit | claim | remittance | reclass
            $table->decimal('amount', 18, 2); // positive reduces what is owed; negative increases it
            $table->string('source_type', 30);
            $table->string('source_id', 40);
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->date('applied_on');
            $table->timestamp('created_at')->nullable();

            $table->unique(['receivable_id', 'kind', 'source_type', 'source_id'], 'fin_receivable_apps_unique');
        });

        Schema::create('fin_writeoffs', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('receivable_id')->constrained('fin_receivables')->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('reason', 250);
            $table->string('status', 10)->default('requested'); // requested | approved | rejected
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note', 250)->nullable();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        // ---- cash and bank (S06) ------------------------------------------------------------------------------------------------------------------
        Schema::create('fin_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('fin_accounts')->restrictOnDelete(); // the ledger account this bank/till/wallet posts to
            $table->string('name', 120);
            $table->string('kind', 10); // bank | cash | wallet | gateway
            $table->string('bank_name', 120)->nullable();
            $table->string('number_last4', 8)->nullable(); // never the full number
            $table->string('payment_method_code', 30)->nullable(); // Billing payment method this account receives (CASH, CARD, MFS...)
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'kind']);
        });

        // ---- payables (S05) -----------------------------------------------------------------------------------------------------------------------
        Schema::create('fin_payables', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('supplier_id');
            $table->string('source_type', 20)->default('supplier_invoice');
            $table->unsignedBigInteger('source_id');
            $table->string('invoice_number', 60);
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 18, 2); // what the supplier invoice says (tax included)
            $table->decimal('settled', 18, 2)->default(0);
            $table->string('status', 10)->default('open'); // open | settled
            $table->timestamps();

            $table->unique(['organization_id', 'source_type', 'source_id'], 'fin_payables_source_unique');
            $table->index(['organization_id', 'supplier_id', 'status'], 'fin_payables_supplier');
        });

        Schema::create('fin_supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30);
            $table->unsignedBigInteger('supplier_id');
            $table->date('payment_date');
            $table->string('method', 10); // bank | cash | wallet | cheque
            $table->foreignId('bank_account_id')->nullable()->constrained('fin_bank_accounts')->nullOnDelete();
            $table->decimal('gross_amount', 18, 2);
            $table->decimal('withheld_ait', 18, 2)->default(0);
            $table->decimal('withheld_vat', 18, 2)->default(0);
            $table->decimal('net_amount', 18, 2);
            $table->string('reference', 80)->nullable();
            $table->string('status', 10)->default('draft'); // draft | approved | paid | cancelled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->string('note', 250)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status']);
        });
        Schema::create('fin_supplier_payment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('fin_supplier_payments')->cascadeOnDelete();
            $table->foreignId('payable_id')->constrained('fin_payables')->restrictOnDelete();
            $table->decimal('amount', 18, 2);

            $table->unique(['payment_id', 'payable_id']);
        });

        Schema::create('fin_bank_deposits', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30);
            $table->foreignId('bank_account_id')->constrained('fin_bank_accounts')->restrictOnDelete();
            $table->foreignId('from_account_id')->nullable()->constrained('fin_bank_accounts')->nullOnDelete(); // the till or wallet the money leaves
            $table->unsignedBigInteger('cashier_session_id')->nullable();
            $table->date('deposit_date');
            $table->decimal('amount', 18, 2);
            $table->string('reference', 80)->nullable();
            $table->string('status', 10)->default('banked'); // banked | cancelled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->unique('cashier_session_id'); // a till session is banked once
        });

        Schema::create('fin_bank_statements', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('fin_bank_accounts')->restrictOnDelete();
            $table->string('file_name', 150)->nullable();
            $table->date('period_from');
            $table->date('period_to');
            $table->decimal('opening_balance', 18, 2)->nullable();
            $table->decimal('closing_balance', 18, 2)->nullable();
            $table->unsignedInteger('line_count')->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 10)->default('open'); // open | reconciled
            $table->timestamps();
        });

        Schema::create('fin_bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statement_id')->constrained('fin_bank_statements')->cascadeOnDelete();
            $table->date('txn_date');
            $table->string('description', 250)->nullable();
            $table->string('reference', 80)->nullable();
            $table->decimal('money_in', 18, 2)->default(0);
            $table->decimal('money_out', 18, 2)->default(0);
            $table->string('line_hash', 64);
            $table->string('status', 10)->default('unmatched'); // unmatched | matched | ignored
            $table->string('matched_type', 20)->nullable(); // deposit | supplier_payment | settlement | journal | adjustment
            $table->unsignedBigInteger('matched_id')->nullable();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->string('note', 250)->nullable();

            $table->unique(['statement_id', 'line_hash']);
            $table->index(['statement_id', 'status']);
        });

        // ---- tax (S07) ----------------------------------------------------------------------------------------------------------------------------
        Schema::create('fin_tax_codes', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 120);
            $table->string('kind', 12); // vat_output | vat_input | ait | tds | other
            $table->decimal('rate', 8, 4)->default(0); // set by the accountant; the starter codes carry 0 until confirmed
            $table->foreignId('account_id')->constrained('fin_accounts')->restrictOnDelete();
            $table->boolean('active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('note', 200)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('fin_tax_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_code_id')->nullable()->constrained('fin_tax_codes')->nullOnDelete();
            $table->string('kind', 12); // vat_output | vat_input | ait | tds
            $table->date('entry_date');
            $table->foreignId('period_id')->nullable()->constrained('fin_periods')->nullOnDelete();
            $table->string('source_type', 30);
            $table->string('source_id', 40);
            $table->string('document_number', 60)->nullable();
            $table->string('party_type', 12)->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('party_name', 150)->nullable();
            $table->string('party_tax_id', 40)->nullable(); // BIN / TIN as recorded on the document
            $table->decimal('base_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2);
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['organization_id', 'source_type', 'source_id', 'kind'], 'fin_tax_entries_unique');
            $table->index(['organization_id', 'kind', 'entry_date']);
        });

        // ---- budgets (S09) ------------------------------------------------------------------------------------------------------------------------
        Schema::create('fin_budgets', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('fin_fiscal_years')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('status', 10)->default('draft'); // draft | approved
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'fiscal_year_id', 'name'], 'fin_budgets_unique');
        });

        Schema::create('fin_budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('fin_budgets')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('fin_accounts')->restrictOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('fin_cost_centers')->nullOnDelete();
            $table->unsignedTinyInteger('period_number'); // 1..12
            $table->decimal('amount', 18, 2);

            $table->unique(['budget_id', 'account_id', 'cost_center_id', 'period_number'], 'fin_budget_lines_unique');
        });

        // ---- fixed assets (S10) -------------------------------------------------------------------------------------------------------------------
        Schema::create('fin_assets', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('tag', 30);
            $table->string('name', 150);
            $table->string('category', 30)->nullable();
            $table->foreignId('cost_center_id')->nullable()->constrained('fin_cost_centers')->nullOnDelete();
            $table->date('acquired_on');
            $table->decimal('cost', 18, 2);
            $table->decimal('salvage_value', 18, 2)->default(0);
            $table->unsignedSmallInteger('life_months');
            $table->date('depreciation_from'); // first month that is charged
            $table->decimal('accumulated', 18, 2)->default(0);
            $table->string('status', 20)->default('active'); // active | fully_depreciated | disposed
            $table->foreignId('asset_account_id')->constrained('fin_accounts')->restrictOnDelete();
            $table->foreignId('accumulated_account_id')->constrained('fin_accounts')->restrictOnDelete();
            $table->foreignId('expense_account_id')->constrained('fin_accounts')->restrictOnDelete();
            $table->unsignedBigInteger('supplier_invoice_id')->nullable();
            $table->date('disposed_on')->nullable();
            $table->string('note', 250)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'tag']);
        });

        Schema::create('fin_depreciation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('fin_assets')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('fin_periods')->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['asset_id', 'period_id']);
        });

        // Where the stock valuation posting stopped (the stock ledger is append-only, so a position in it is a safe place to resume).
        Schema::create('fin_cursors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40);
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
        });

        // ---- consultant fee sharing (S11) ---------------------------------------------------------------------------------------------------------
        Schema::create('fin_fee_share_rules', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_user_id')->nullable()->constrained('users')->nullOnDelete(); // null = applies to every doctor
            $table->string('category_code', 40)->nullable(); // Billing category; null = every category
            $table->decimal('percent', 6, 2);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('active')->default(true);
            $table->string('note', 200)->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'active']);
        });

        Schema::create('fin_fee_share_runs', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('fin_periods')->cascadeOnDelete();
            $table->foreignId('provider_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('revenue_base', 18, 2);
            $table->decimal('amount', 18, 2);
            $table->string('status', 10)->default('accrued'); // accrued | paid
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'period_id', 'provider_user_id'], 'fin_fee_share_runs_unique');
        });
    }

    public function down(): void
    {
        foreach (['fin_cursors', 'fin_fee_share_runs', 'fin_fee_share_rules', 'fin_depreciation', 'fin_assets', 'fin_budget_lines', 'fin_budgets', 'fin_tax_entries', 'fin_tax_codes', 'fin_bank_statement_lines', 'fin_bank_statements', 'fin_bank_deposits', 'fin_supplier_payment_lines',
            'fin_supplier_payments', 'fin_payables', 'fin_bank_accounts', 'fin_writeoffs', 'fin_receivable_applications', 'fin_receivables'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
