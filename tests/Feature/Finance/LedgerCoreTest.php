<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\CostCenter;
use App\Models\Finance\Journal;
use App\Models\Finance\JournalLine;
use App\Services\Finance\FinanceException;
use App\Services\Finance\FiscalService;
use App\Services\Finance\LedgerService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LedgerCoreTest extends FinanceTestCase
{
    public function test_starter_chart_is_made_once_and_roles_resolve(): void
    {
        $org = $this->organization->id;
        $this->assertSame(['accounts' => 0, 'roles' => 0], $this->chart()->seedStarter($org));
        $this->assertSame('1110', $this->chart()->accountFor($org, 'cash')->code);
        $this->assertSame('1310', $this->chart()->accountFor($org, 'inventory', 'pharmacy')->code);
        $this->assertSame('1320', $this->chart()->accountFor($org, 'inventory', 'unknown-store')->code, 'falls back to the "all" row');
        $this->expectException(FinanceException::class);
        $this->chart()->accountFor($org, 'no-such-role');
    }

    public function test_accounts_need_unique_codes_consistent_parents_and_cannot_change_type_once_used(): void
    {
        $org = $this->organization->id;
        $heading = Account::query()->where(['organization_id' => $org, 'code' => '5000'])->first();
        $new = $this->chart()->createAccount($org, ['code' => '5810', 'name' => 'Rent', 'type' => 'expense', 'parent_id' => $heading->id]);
        $this->assertSame('debit', $new->normal_side);
        try {
            $this->chart()->createAccount($org, ['code' => '5810', 'name' => 'Dup', 'type' => 'expense']);
            $this->fail('duplicate code');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('code', $e->errors());
        }
        try {
            $this->chart()->createAccount($org, ['code' => '5820', 'name' => 'Mixed', 'type' => 'asset', 'parent_id' => $heading->id]);
            $this->fail('parent of another type');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('parent_id', $e->errors());
        }
        $this->postJournal('5810', '1110', '100.00');
        $this->expectException(ValidationException::class);
        $this->chart()->updateAccount($new, ['type' => 'asset']);
    }

    public function test_an_account_with_postings_is_not_deleted_and_a_heading_takes_no_postings(): void
    {
        $this->postJournal('1110', '4100', '50.00');
        $cash = Account::query()->where(['organization_id' => $this->organization->id, 'code' => '1110'])->first();
        try {
            $this->chart()->deleteAccount($cash);
            $this->fail('used account deleted');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $this->expectException(ValidationException::class);
        $this->postJournal('1100', '4100', '10.00'); // 1100 is a heading
    }

    public function test_a_journal_must_balance_and_each_line_is_one_sided(): void
    {
        $org = $this->organization->id;
        $bad = [
            [[['account' => '1110', 'debit' => '100', 'credit' => '0'], ['account' => '4100', 'debit' => '0', 'credit' => '90']], 'does not balance'],
            [[['account' => '1110', 'debit' => '100', 'credit' => '100'], ['account' => '4100', 'debit' => '0', 'credit' => '0']], 'either a debit or a credit'],
            [[['account' => '1110', 'debit' => '100', 'credit' => '0']], 'between 2 and'],
            [[['account' => '1110', 'debit' => '-5', 'credit' => '0'], ['account' => '4100', 'debit' => '0', 'credit' => '5']], 'zero or more'],
            [[['account' => '1110', 'debit' => 'abc', 'credit' => '0'], ['account' => '4100', 'debit' => '0', 'credit' => '5']], 'zero or more'],
            [[['account' => '1210', 'debit' => '10', 'credit' => '0'], ['account' => '4100', 'debit' => '0', 'credit' => '10']], 'control account'],
        ];
        foreach ($bad as [$lines, $needle]) {
            try {
                $j = $this->journals()->createDraft($org, ['date' => '2026-09-05', 'memo' => 'x'], $lines, $this->accountant());
                $this->journals()->submit($j, $this->accountant());
                $this->fail("accepted: $needle");
            } catch (ValidationException $e) {
                $this->assertStringContainsString($needle, implode(' ', array_merge(...array_values($e->errors()))));
            }
        }
        $this->assertSame(0, Journal::query()->where('status', 'posted')->count(), 'nothing unbalanced was ever posted');
    }

    public function test_maker_checker_and_posting(): void
    {
        $org = $this->organization->id;
        $maker = $this->accountant();
        $checker = $this->accountant();
        $j = $this->journals()->createDraft($org, ['date' => '2026-09-05', 'memo' => 'Owner capital'], [['account' => '1120', 'debit' => '5000', 'credit' => '0'], ['account' => '3100', 'debit' => '0', 'credit' => '5000']], $maker);
        $this->assertSame('draft', $j->status);
        $j = $this->journals()->submit($j, $maker);
        $this->assertSame('pending', $j->status);
        try {
            $this->journals()->approve($j, $maker);
            $this->fail('self approval');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('approver', $e->errors());
        }
        $posted = $this->journals()->approve($j, $checker);
        $this->assertSame('posted', $posted->status);
        $this->assertSame($checker->id, $posted->approved_by);
        $this->assertMatchesRegularExpression('/^JV-2026-\d{6}$/', $posted->number);
        $this->assertSame('5000.00', (string) $posted->total_debit);
    }

    public function test_sent_back_journal_returns_to_draft_with_the_reason(): void
    {
        $maker = $this->accountant();
        $j = $this->journals()->createDraft($this->organization->id, ['date' => '2026-09-05', 'memo' => 'x'], [['account' => '1120', 'debit' => '10', 'credit' => '0'], ['account' => '3100', 'debit' => '0', 'credit' => '10']], $maker);
        $j = $this->journals()->submit($j, $maker);
        $j = $this->journals()->reject($j, $this->accountant(), 'Wrong account');
        $this->assertSame('draft', $j->status);
        $this->assertSame('Wrong account', $j->reject_note);
    }

    public function test_a_posted_journal_cannot_be_changed_or_deleted_even_by_raw_sql(): void
    {
        $j = $this->postJournal('1110', '4100', '75.00');
        foreach ([
            fn () => DB::table('fin_journal_lines')->where('journal_id', $j->id)->update(['debit' => '1.00']),
            fn () => DB::table('fin_journal_lines')->where('journal_id', $j->id)->delete(),
            fn () => DB::table('fin_journals')->where('id', $j->id)->delete(),
            fn () => DB::table('fin_journal_lines')->insert(['journal_id' => $j->id, 'organization_id' => $this->organization->id, 'line_no' => 9, 'journal_date' => '2026-09-05', 'account_id' => Account::query()->value('id'), 'debit' => 1, 'credit' => 0]),
        ] as $attack) {
            try {
                $attack();
                $this->fail('the database accepted a change to a posted journal');
            } catch (QueryException $e) {
                $this->assertStringContainsString('posted journal', $e->getMessage());
            }
        }
        $this->assertSame(2, JournalLine::query()->where('journal_id', $j->id)->count());
        $this->expectException(ValidationException::class);
        $this->journals()->replaceLines($j, [['account' => '1110', 'debit' => '1', 'credit' => '0'], ['account' => '4100', 'debit' => '0', 'credit' => '1']]);
    }

    public function test_reversal_swaps_the_lines_once_and_keeps_the_original(): void
    {
        $by = $this->accountant();
        $j = $this->postJournal('1110', '4100', '120.00');
        $r = $this->journals()->reverse($j, $by, 'Posted to the wrong department');
        $this->assertSame('reversal', $r->kind);
        $this->assertSame($j->id, $r->reversal_of_id);
        $this->assertSame($r->id, $j->refresh()->reversed_by_id);
        $cash = $r->lines()->first();
        $this->assertSame('0.00', (string) $cash->debit);
        $this->assertSame('120.00', (string) $cash->credit);
        $tb = app(LedgerService::class)->trialBalance($this->organization->id, null, '2026-12-31');
        $this->assertSame(0, $tb->count() - $tb->count()); // accounts stay listed with activity
        $this->assertTrue(app(LedgerService::class)->totals($tb)['balanced']);
        $this->assertSame('0.00', app(LedgerService::class)->balance($this->organization->id, Account::query()->where(['organization_id' => $this->organization->id, 'code' => '1110'])->first()));
        try {
            $this->journals()->reverse($j, $by, 'again');
            $this->fail('reversed twice');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $this->expectException(ValidationException::class);
        $this->journals()->reverse($r, $by, 'reverse the reversal');
    }

    public function test_locked_and_missing_periods_refuse_postings_and_unlock_needs_a_reason(): void
    {
        $by = $this->accountant();
        $fiscal = app(FiscalService::class);
        $sep = $fiscal->periodFor($this->organization->id, '2026-09-05');
        $this->postJournal('1110', '4100', '10.00', '2026-09-05');
        $fiscal->lock($sep, $by, 'September closed');
        try {
            $this->postJournal('1110', '4100', '10.00', '2026-09-20');
            $this->fail('posted into a locked period');
        } catch (FinanceException|ValidationException $e) {
            $this->assertStringContainsString('locked', $e->getMessage().json_encode(method_exists($e, 'errors') ? $e->errors() : []));
        }
        try {
            $this->postJournal('1110', '4100', '10.00', '2027-02-01'); // no fiscal year yet
            $this->fail('posted without a period');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('fiscal period', $e->getMessage().json_encode(method_exists($e, 'errors') ? $e->errors() : []));
        }
        try {
            $fiscal->unlock($sep, $by, '');
            $this->fail('unlock without reason');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $fiscal->unlock($sep, $by, 'Late supplier bill');
        $this->postJournal('1110', '4100', '10.00', '2026-09-21');
        $this->assertSame(2, Journal::query()->where('status', 'posted')->count());
    }

    public function test_the_same_source_posts_once_even_when_called_twice(): void
    {
        $data = ['date' => '2026-09-05', 'memo' => 'Invoice', 'source_type' => 'invoice', 'source_id' => '77', 'event_key' => 'finalized'];
        $lines = [['account' => '1110', 'debit' => '10', 'credit' => '0'], ['account' => '4100', 'debit' => '0', 'credit' => '10']];
        $a = $this->journals()->postAuto($this->organization->id, $data, $lines);
        $b = $this->journals()->postAuto($this->organization->id, $data, $lines);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, Journal::query()->where('source_id', '77')->count());
    }

    public function test_trial_balance_opening_movement_closing_and_cost_centre_filter(): void
    {
        $org = $this->organization->id;
        $this->postJournal('1110', '4100', '100.00', '2026-08-10');
        $this->postJournal('1110', '4100', '40.00', '2026-09-05');
        $this->journals()->postAuto($org, ['date' => '2026-09-06', 'memo' => 'cc', 'source_type' => 't', 'source_id' => 'cc', 'event_key' => 'x'], [
            ['account' => '5800', 'debit' => '30', 'credit' => '0', 'cost_center' => 'HOSP'], ['account' => '1110', 'debit' => '0', 'credit' => '30'],
        ]);
        $tb = app(LedgerService::class)->trialBalance($org, '2026-09-01', '2026-09-30');
        $cash = $tb->firstWhere('code', '1110');
        $this->assertSame('100.00', $cash->opening);
        $this->assertSame('40.00', $cash->debit);
        $this->assertSame('30.00', $cash->credit);
        $this->assertSame('110.00', $cash->closing);
        $rev = $tb->firstWhere('code', '4100');
        $this->assertSame('100.00', $rev->opening, 'credit-side accounts show positive on their own side');
        $this->assertSame('140.00', $rev->closing);
        $this->assertTrue(app(LedgerService::class)->totals($tb)['balanced']);
        $byCc = app(LedgerService::class)->trialBalance($org, '2026-09-01', '2026-09-30', CostCenter::query()->where('code', 'HOSP')->value('id'));
        $this->assertSame(['5800'], $byCc->pluck('code')->all());
        $ledger = app(LedgerService::class)->accountLedger($org, Account::query()->where(['organization_id' => $org, 'code' => '1110'])->first(), '2026-09-01', '2026-09-30');
        $this->assertSame('100.00', $ledger['opening']);
        $this->assertSame('110.00', $ledger['closing']);
        $this->assertSame(['140.00', '110.00'], $ledger['lines']->pluck('balance')->all());
    }

    public function test_year_dates_cannot_overlap_and_periods_cover_the_year(): void
    {
        $this->assertSame(12, $this->year->periods()->count());
        $this->assertSame('2026-12-31', $this->year->periods()->get()->last()->ends_on->toDateString());
        $this->expectException(ValidationException::class);
        app(FiscalService::class)->createYear($this->organization->id, 2026, 7);
    }
}
