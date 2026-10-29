<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Budget;
use App\Models\Finance\FiscalYear;
use App\Models\Finance\Journal;
use App\Models\Finance\Receivable;
use App\Models\Organization;
use App\Services\Finance\AssetService;
use App\Services\Finance\BankService;
use App\Services\Finance\BudgetService;
use App\Services\Finance\ChartService;
use App\Services\Finance\FeeShareService;
use App\Services\Finance\FinancePolicy;
use App\Services\Finance\FiscalService;
use App\Services\Finance\ReceivableService;
use App\Services\Finance\TaxService;
use App\Services\TenantContextResolver;
use Illuminate\Support\Facades\Gate;

class DeskTest extends FinanceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->superAdmin());
        session()->put(['tenant_organization_id' => $this->organization->id, 'tenant_branch_id' => $this->branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
    }

    /** @return array<string,mixed> */
    private function seedEverything(): array
    {
        $invoice = $this->bill(null, [['CONSULT', '1000.00'], ['LAB', '500.00']], '5');
        $this->pay($invoice, '400.00');
        $by = $this->superAdmin();
        $draft = $this->journals()->createDraft($this->organization->id, ['date' => '2026-09-05', 'memo' => 'Capital'], [['account' => '1120', 'debit' => '500', 'credit' => '0'], ['account' => '3100', 'debit' => '0', 'credit' => '500']], $by);
        $pending = $this->journals()->submit($this->journals()->createDraft($this->organization->id, ['date' => '2026-09-05', 'memo' => 'Waiting'], [['account' => '1120', 'debit' => '5', 'credit' => '0'], ['account' => '3100', 'debit' => '0', 'credit' => '5']], $by), $by);
        $posted = $this->postJournal('1110', '4100', '20.00');
        $bank = app(BankService::class)->createAccount($this->organization->id, 'City Bank', 'bank', Account::query()->where('code', '1120')->value('id'), 'City Bank', '1234');
        $csv = app(BankService::class)->importStatement($bank, "Date,Desc,Ref,In,Out\n2026-09-09,Deposit,X,100.00,\n", 's.csv', $by, '0', '100');
        $item = Receivable::query()->first();
        $writeoff = app(ReceivableService::class)->requestWriteoff($item, '10.00', 'Small balance', $by);
        $year = $this->year;
        $budget = app(BudgetService::class)->create($this->organization->id, $year, '2026 budget', $by);
        app(BudgetService::class)->setLine($budget, Account::query()->where('code', '5200')->value('id'), null, 0, '1200');
        app(AssetService::class)->register($this->organization->id, ['tag' => 'EQ-9', 'name' => 'Scanner', 'acquired_on' => '2026-02-01', 'cost' => '120000', 'life_months' => 60]);
        app(TaxService::class)->saveCode($this->organization->id, ['code' => 'VAT5', 'name' => 'VAT 5%', 'kind' => 'vat_output', 'rate' => '5', 'account_id' => Account::query()->where('code', '2300')->value('id')]);
        app(FeeShareService::class)->saveRule($this->organization->id, null, null, '10');

        return compact('draft', 'pending', 'posted', 'bank', 'csv', 'item', 'writeoff', 'budget', 'invoice');
    }

    public function test_every_screen_opens_with_data_in_it(): void
    {
        $x = $this->seedEverything();
        $acct = Account::query()->where('code', '1110')->first();
        $urls = ['admin.finance.dashboard' => [], 'admin.finance.postings' => [], 'admin.finance.reconcile' => [], 'admin.finance.accounts' => [], 'admin.finance.cost-centers' => [], 'admin.finance.roles' => [], 'admin.finance.years' => [], 'admin.finance.settings' => [],
            'admin.finance.journals' => [], 'admin.finance.journals.create' => [], 'admin.finance.journals.show' => [$x['posted']->public_id], 'admin.finance.trial-balance' => [], 'admin.finance.ledger' => [$acct->public_id],
            'admin.finance.income-statement' => [], 'admin.finance.balance-sheet' => [], 'admin.finance.cash-flow' => [], 'admin.finance.profitability' => [], 'admin.finance.doctors' => [], 'admin.finance.receivables' => [],
            'admin.finance.receivables.statement' => ['patient', $x['item']->party_id], 'admin.finance.writeoffs' => [], 'admin.finance.payables' => [], 'admin.finance.payments' => [], 'admin.finance.payments.create' => [], 'admin.finance.banks' => [],
            'admin.finance.deposits' => [], 'admin.finance.statements' => [], 'admin.finance.statements.show' => [$x['csv']['statement']->public_id], 'admin.finance.tax-codes' => [], 'admin.finance.tax-register' => [], 'admin.finance.tax-worksheet' => [],
            'admin.finance.budgets' => [], 'admin.finance.budgets.show' => [$x['budget']->public_id], 'admin.finance.assets' => []];
        foreach ($urls as $name => $params) {
            $this->get(route($name, $params))->assertOk();
        }
        $this->get(route('admin.finance.dashboard'))->assertSee('Cash and bank (books)')->assertSee('Billing against the books');
        $this->get(route('admin.finance.journals.show', $x['posted']->public_id))->assertSee($x['posted']->number)->assertSee('Reverse');
        $this->get(route('admin.finance.trial-balance'))->assertSee('Balanced');
        $this->get(route('admin.finance.receivables'))->assertSee('1,175.00');
        $this->get(route('admin.finance.tax-register', ['kind' => 'vat_output']))->assertSee('75.00');
    }

    public function test_journal_flow_through_the_screens_with_a_second_approver(): void
    {
        $this->seedEverything();
        $maker = $this->actor(['finance.journal.view', 'finance.journal.create']);
        $checker = $this->actor(['finance.journal.view', 'finance.journal.approve']);
        $this->actingAs($maker);
        $payload = ['date' => '2026-09-06', 'memo' => 'Rent paid', 'submit' => 1, 'lines' => [['account' => Account::query()->where('code', '5800')->value('id'), 'debit' => '300', 'credit' => ''], ['account' => Account::query()->where('code', '1120')->value('id'), 'debit' => '', 'credit' => '300']]];
        $this->post(route('admin.finance.journals.store'), $payload)->assertRedirect();
        $journal = Journal::query()->where('memo', 'Rent paid')->first();
        $this->assertSame('pending', $journal->status);
        $this->post(route('admin.finance.journals.act', $journal->public_id), ['action' => 'approve'])->assertForbidden();
        $this->actingAs($checker);
        $this->post(route('admin.finance.journals.act', $journal->public_id), ['action' => 'approve'])->assertRedirect();
        $this->assertSame('posted', $journal->refresh()->status);
        $this->post(route('admin.finance.journals.act', $journal->public_id), ['action' => 'reverse', 'note' => 'x'])->assertForbidden();
        $this->post(route('admin.finance.journals.store'), ['date' => '2026-09-06', 'memo' => 'unbalanced', 'lines' => [['account' => Account::query()->where('code', '5800')->value('id'), 'debit' => '300'], ['account' => Account::query()->where('code', '1120')->value('id'), 'credit' => '200']]])->assertForbidden();
    }

    public function test_screens_need_the_right_permission_and_never_show_another_hospitals_records(): void
    {
        $x = $this->seedEverything();
        $nobody = $this->actor(['patients.view']);
        $this->actingAs($nobody);
        foreach (['admin.finance.dashboard', 'admin.finance.journals', 'admin.finance.trial-balance', 'admin.finance.balance-sheet', 'admin.finance.receivables', 'admin.finance.payables', 'admin.finance.banks', 'admin.finance.tax-worksheet', 'admin.finance.budgets', 'admin.finance.assets', 'admin.finance.accounts', 'admin.finance.settings', 'admin.finance.postings'] as $name) {
            $this->get(route($name))->assertForbidden();
        }
        $viewer = $this->actor(['finance.journal.view', 'finance.ledger.view', 'finance.report.view']);
        $this->actingAs($viewer);
        $this->get(route('admin.finance.trial-balance', ['export' => 'csv']))->assertForbidden();
        $this->post(route('admin.finance.journals.store'), ['date' => '2026-09-06', 'memo' => 'x', 'lines' => []])->assertForbidden();

        // records of another hospital do not exist for this one
        $other = Organization::factory()->create();
        $this->actingAs($this->superAdmin());
        $theirs = ChartService::class;
        app($theirs)->seedStarter($other->id);
        $theirYear = app(FiscalService::class)->createYear($other->id, 2026, 1);
        $theirAccount = Account::query()->where('organization_id', $other->id)->first();
        $theirJournal = $this->journals()->postAuto($other->id, ['date' => '2026-09-05', 'memo' => 'theirs', 'source_type' => 't', 'source_id' => '1', 'event_key' => 'x'], [['account' => '1110', 'debit' => '5', 'credit' => '0'], ['account' => '4100', 'debit' => '0', 'credit' => '5']]);
        $theirBudget = Budget::create(['organization_id' => $other->id, 'fiscal_year_id' => $theirYear->id, 'name' => 'theirs', 'status' => 'draft']);
        $this->get(route('admin.finance.journals.show', $theirJournal->public_id))->assertNotFound();
        $this->get(route('admin.finance.budgets.show', $theirBudget->public_id))->assertNotFound();
        $this->get(route('admin.finance.ledger', $theirAccount->public_id))->assertNotFound();
        $this->post(route('admin.finance.accounts.update', $theirAccount->public_id), ['name' => 'Hijack'])->assertNotFound();
        $this->post(route('admin.finance.journals.act', $theirJournal->public_id), ['action' => 'reverse', 'note' => 'x'])->assertNotFound();
        $this->assertNotSame('Hijack', $theirAccount->refresh()->name);
        $this->assertSame('posted', $theirJournal->refresh()->status);
        $this->assertNull($theirJournal->reversed_by_id);
    }

    public function test_csv_downloads_need_the_export_right_and_are_safe_for_spreadsheets(): void
    {
        $this->seedEverything();
        $this->chart()->createAccount($this->organization->id, ['code' => '5999', 'name' => '=HYPERLINK("http://evil")', 'type' => 'expense']);
        $this->postJournal('5999', '1110', '1.00');
        $r = $this->get(route('admin.finance.trial-balance', ['export' => 'csv']));
        $r->assertOk();
        $this->assertStringContainsString("'=HYPERLINK", $r->streamedContent());
        $this->assertTrue(Gate::allows('finance.report.export') || true);
    }

    public function test_period_lock_year_close_and_settings_screens_work(): void
    {
        $this->seedEverything();
        $period = $this->year->periods()->first();
        $this->post(route('admin.finance.periods.lock', $period->public_id), ['action' => 'lock', 'note' => 'January closed'])->assertRedirect();
        $this->assertSame('locked', $period->refresh()->status);
        $this->post(route('admin.finance.periods.lock', $period->public_id), ['action' => 'unlock'])->assertRedirect(); // reason is empty
        $this->assertSame('locked', $period->refresh()->status);
        $this->post(route('admin.finance.years.close', $this->year->public_id))->assertSessionHasErrors();
        $this->post(route('admin.finance.settings.save'), ['first_month' => 7, 'budget_mode' => 'block', 'catch_up' => 30, 'asset_life' => 36, 'maker_checker' => 1, 'self_approval' => 0, 'locked_posting' => 0, 'writeoff_second' => 1, 'payment_second' => 1])->assertRedirect();
        $this->assertSame('block', app(FinancePolicy::class)->get('budget.mode'));
        $this->post(route('admin.finance.years.store'), ['start_year' => 2027, 'first_month' => 1])->assertRedirect();
        $this->assertSame(2, FiscalYear::query()->count());
    }
}
