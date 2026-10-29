<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Phase 21, part 1: fiscal years and periods, cost centres, chart of accounts, account roles, journals and their lines, and the posting queue (S01-S03). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 8)->default('open'); // open | closed
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closing_journal_id')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
            $table->index(['organization_id', 'starts_on', 'ends_on']);
        });

        Schema::create('fin_periods', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('fin_fiscal_years')->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->string('name', 40);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 8)->default('open'); // open | locked | closed
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->string('lock_note', 200)->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'number']);
            $table->index(['organization_id', 'starts_on', 'ends_on']);
        });

        Schema::create('fin_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('fin_cost_centers')->nullOnDelete();
            $table->string('code', 20);
            $table->string('name', 120);
            $table->string('kind', 8)->default('cost'); // cost | profit
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('fin_accounts', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('fin_accounts')->nullOnDelete();
            $table->string('code', 20);
            $table->string('name', 150);
            $table->string('type', 9); // asset | liability | equity | revenue | expense
            $table->string('normal_side', 6); // debit | credit
            $table->boolean('postable')->default(true); // false = a heading that only groups others
            $table->string('control', 12)->nullable(); // receivable | payable | bank | cash | inventory | tax: a control account needs a party or a bank account on its lines
            $table->boolean('active')->default(true);
            $table->boolean('system')->default(false); // made by the starter chart; cannot be deleted
            $table->string('note', 200)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'type']);
        });

        // Which account a posting uses for a named purpose (revenue of a category, the bank account of a payment method...). The rules are data, not code.
        Schema::create('fin_account_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('role', 40);
            $table->string('scope_key', 60)->default('all');
            $table->foreignId('account_id')->constrained('fin_accounts')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'role', 'scope_key'], 'fin_account_roles_unique');
        });

        Schema::create('fin_journals', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->date('journal_date');
            $table->foreignId('period_id')->nullable()->constrained('fin_periods')->nullOnDelete();
            $table->string('kind', 12)->default('manual'); // manual | auto | opening | reversal | closing | depreciation
            $table->string('status', 10)->default('draft'); // draft | pending | posted
            $table->string('memo', 250);
            $table->string('reference', 80)->nullable();
            $table->char('currency', 3)->default('BDT');
            $table->decimal('total_debit', 18, 2)->default(0);
            $table->decimal('total_credit', 18, 2)->default(0);
            $table->string('source_type', 40)->nullable();
            $table->string('source_id', 60)->nullable();
            $table->string('event_key', 40)->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('fin_journals')->nullOnDelete();
            $table->foreignId('reversed_by_id')->nullable()->constrained('fin_journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->string('reject_note', 250)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->unique(['organization_id', 'source_type', 'source_id', 'event_key'], 'fin_journals_source_unique');
            $table->index(['organization_id', 'status', 'journal_date']);
        });

        Schema::create('fin_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('fin_journals')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('line_no');
            $table->date('journal_date');
            $table->foreignId('account_id')->constrained('fin_accounts')->restrictOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('fin_cost_centers')->nullOnDelete();
            $table->string('party_type', 12)->nullable(); // patient | corporate | payer | supplier | employee | bank
            $table->unsignedBigInteger('party_id')->nullable();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->string('memo', 200)->nullable();
            $table->unsignedBigInteger('tax_code_id')->nullable();

            $table->unique(['journal_id', 'line_no']);
            $table->index(['organization_id', 'account_id', 'journal_date'], 'fin_lines_account_date');
            $table->index(['party_type', 'party_id']);
            $table->index(['cost_center_id', 'journal_date']);
        });

        // What the engine did with each source record: posted once, or failed with a reason to retry. Unique per source and event, so a repeated event cannot post twice.
        Schema::create('fin_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 40);
            $table->string('source_id', 60);
            $table->string('event_key', 40);
            $table->string('status', 8); // posted | failed | skipped
            $table->foreignId('journal_id')->nullable()->constrained('fin_journals')->nullOnDelete();
            $table->string('error', 400)->nullable();
            $table->unsignedSmallInteger('attempts')->default(1);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'source_type', 'source_id', 'event_key'], 'fin_postings_unique');
            $table->index(['organization_id', 'status']);
        });

        $this->protect();
    }

    /** A posted journal's lines never change: the database refuses it, not only the application. */
    private function protect(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared("CREATE TRIGGER fin_lines_no_update BEFORE UPDATE ON fin_journal_lines WHEN (SELECT status FROM fin_journals WHERE id = OLD.journal_id) <> 'draft' BEGIN SELECT RAISE(ABORT, 'a posted journal cannot be changed'); END");
            DB::unprepared("CREATE TRIGGER fin_lines_no_delete BEFORE DELETE ON fin_journal_lines WHEN (SELECT status FROM fin_journals WHERE id = OLD.journal_id) <> 'draft' BEGIN SELECT RAISE(ABORT, 'a posted journal cannot be changed'); END");
            DB::unprepared("CREATE TRIGGER fin_lines_no_insert BEFORE INSERT ON fin_journal_lines WHEN (SELECT status FROM fin_journals WHERE id = NEW.journal_id) <> 'draft' BEGIN SELECT RAISE(ABORT, 'a posted journal cannot be changed'); END");
            DB::unprepared("CREATE TRIGGER fin_journals_no_delete BEFORE DELETE ON fin_journals WHEN OLD.status <> 'draft' BEGIN SELECT RAISE(ABORT, 'a posted journal cannot be deleted'); END");

            return;
        }

        $check = "IF (SELECT status FROM fin_journals WHERE id = %s.journal_id) <> 'draft' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'a posted journal cannot be changed'; END IF";
        DB::unprepared('CREATE TRIGGER fin_lines_no_update BEFORE UPDATE ON fin_journal_lines FOR EACH ROW BEGIN '.sprintf($check, 'OLD').'; END');
        DB::unprepared('CREATE TRIGGER fin_lines_no_delete BEFORE DELETE ON fin_journal_lines FOR EACH ROW BEGIN '.sprintf($check, 'OLD').'; END');
        DB::unprepared('CREATE TRIGGER fin_lines_no_insert BEFORE INSERT ON fin_journal_lines FOR EACH ROW BEGIN '.sprintf($check, 'NEW').'; END');
        DB::unprepared("CREATE TRIGGER fin_journals_no_delete BEFORE DELETE ON fin_journals FOR EACH ROW BEGIN IF OLD.status <> 'draft' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'a posted journal cannot be deleted'; END IF; END");
    }

    public function down(): void
    {
        foreach (['fin_lines_no_update', 'fin_lines_no_delete', 'fin_lines_no_insert'] as $t) {
            DB::unprepared("DROP TRIGGER IF EXISTS $t");
        }
        DB::unprepared('DROP TRIGGER IF EXISTS fin_journals_no_delete');
        foreach (['fin_postings', 'fin_journal_lines', 'fin_journals', 'fin_account_roles', 'fin_accounts', 'fin_cost_centers', 'fin_periods', 'fin_fiscal_years'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
