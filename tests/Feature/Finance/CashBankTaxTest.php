<?php

namespace Tests\Feature\Finance;

use App\Models\Billing\BillingCashierSession;
use App\Models\Finance\Account;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankStatementLine;
use App\Models\Inventory\InventoryItem;
use App\Services\Finance\BankService;
use App\Services\Finance\LedgerService;
use App\Services\Finance\StockPostingService;
use App\Services\Finance\TaxService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashBankTaxTest extends FinanceTestCase
{
    private function bal(string $code): string
    {
        return app(LedgerService::class)->balance($this->organization->id, Account::query()->where(['organization_id' => $this->organization->id, 'code' => $code])->first());
    }

    private function bank(string $name = 'City Bank', string $code = '1120', string $kind = 'bank'): BankAccount
    {
        return app(BankService::class)->createAccount($this->organization->id, $name, $kind, Account::query()->where(['organization_id' => $this->organization->id, 'code' => $code])->value('id'), $name, '9876');
    }

    private function tillSession(string $actual = '5050.00', string $variance = '0.00'): BillingCashierSession
    {
        return BillingCashierSession::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'user_id' => $this->superAdmin()->id, 'status' => 'closed', 'opening_balance' => '50.00', 'expected_closing' => bcsub($actual, $variance, 2), 'actual_closing' => $actual,
            'variance' => $variance, 'opened_at' => '2026-09-08 04:00:00', 'closed_at' => '2026-09-08 12:00:00']);
    }

    public function test_a_closed_till_is_banked_once_and_a_shortage_goes_to_over_and_short(): void
    {
        $invoice = $this->bill(null, [['CONSULT', '5000.00']]);
        $this->pay($invoice, '5000.00');
        $this->assertSame('5000.00', $this->bal('1110'));
        $bank = $this->bank();
        $session = $this->tillSession('4990.00', '-10.00'); // 10 short, 50 float stays
        $deposit = app(BankService::class)->depositSession($session, $bank, $this->accountant());
        $this->assertSame('4940.00', (string) $deposit->amount);
        $this->assertSame('4940.00', $this->bal('1120'));
        $this->assertSame('10.00', $this->bal('5900'));
        $this->assertSame('50.00', $this->bal('1110'), 'the till keeps its float, so the books equal the real cash');
        $this->expectException(ValidationException::class);
        app(BankService::class)->depositSession($session, $bank, $this->accountant());
    }

    public function test_an_open_session_cannot_be_banked_and_transfers_need_two_different_accounts(): void
    {
        $bank = $this->bank();
        $open = $this->tillSession();
        $open->update(['status' => 'open']);
        try {
            app(BankService::class)->depositSession($open, $bank, $this->accountant());
            $this->fail('banked an open session');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $wallet = $this->bank('bKash', '1130', 'wallet');
        $this->postJournal('1130', '4100', '300.00');
        app(BankService::class)->transfer($this->organization->id, $wallet, $bank, '300.00', '2026-09-09', $this->accountant());
        $this->assertSame('0.00', $this->bal('1130'));
        $this->assertSame('300.00', $this->bal('1120'));
        $this->expectException(ValidationException::class);
        app(BankService::class)->transfer($this->organization->id, $bank, $bank, '1.00', '2026-09-09', $this->accountant());
    }

    public function test_statement_import_matching_and_reconciliation(): void
    {
        $bank = $this->bank();
        $svc = app(BankService::class);
        $by = $this->accountant();
        $svc->depositSession($this->tillSession('1050.00'), $bank, $by); // 1,000 banked 8 Sep
        $csv = "Date,Description,Reference,In,Out\n2026-09-09,Cash deposit,DEP1,1000.00,\n2026-09-10,Monthly fee,FEE,,25.00\n2026-09-10,Monthly fee,FEE,,25.00\n";
        $res = $svc->importStatement($bank, $csv, 'sep.csv', $by, '0.00', '950.00');
        $this->assertSame(3, $res['imported'], 'two identical fee lines are two lines');
        $again = $svc->importStatement($bank, $csv, 'sep.csv', $by);
        $this->assertSame(0, $again['imported']);
        $this->assertSame(3, $again['duplicates']);
        $this->assertSame(1, $svc->autoMatch($res['statement']), 'only the deposit matches by amount and date');
        $summary = $svc->summary($res['statement']);
        $this->assertSame(2, $summary['unmatched']);
        $this->assertFalse($summary['reconciled']);
        foreach (BankStatementLine::query()->where(['statement_id' => $res['statement']->id, 'status' => 'unmatched'])->get() as $line) {
            $svc->postLine($line, '5600', $by, 'Bank charge');
        }
        $s = $svc->summary($res['statement']->refresh());
        $this->assertSame('950.00', $s['ledger']);
        $this->assertSame('0.00', $s['difference']);
        $this->assertTrue($s['reconciled']);
        $this->assertSame('50.00', $this->bal('5600'));
        $this->assertSame('reconciled', $svc->markReconciled($res['statement'], $by)->status);
    }

    public function test_a_bad_statement_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        app(BankService::class)->importStatement($this->bank(), "Date,Desc,Ref,In,Out\nnot-a-date,x,y,1,\n", 'bad.csv', $this->accountant());
    }

    public function test_gateway_settlement_clears_the_clearing_account_and_charges_the_fee(): void
    {
        $invoice = $this->bill(null, [['CONSULT', '1000.00']]);
        $this->pay($invoice, '1000.00', $this->cashMethod('CARD', 'card'));
        $this->assertSame('1000.00', $this->bal('1140'));
        $id = DB::table('portal_settlements')->insertGetId(['public_id' => (string) Str::ulid(), 'organization_id' => $this->organization->id, 'gateway' => 'sandbox', 'file_name' => 's.csv', 'period_from' => '2026-09-01', 'period_to' => '2026-09-09', 'total' => '1000.00', 'fees' => '15.00', 'created_at' => now(), 'updated_at' => now()]);
        app(BankService::class)->settleGateway($this->organization->id, $id, $this->bank(), $this->accountant());
        $this->assertSame('0.00', $this->bal('1140'));
        $this->assertSame('985.00', $this->bal('1120'));
        $this->assertSame('15.00', $this->bal('5600'));
    }

    public function test_vat_worksheet_ties_the_register_to_the_ledger(): void
    {
        $this->bill(null, [['CONSULT', '1000.00']], '5');
        $this->bill(null, [['LAB', '2000.00']], '5');
        $w = app(TaxService::class)->worksheet($this->organization->id, '2026-09-01', '2026-09-30');
        $this->assertSame('150.00', $w['output']['tax']);
        $this->assertSame('3000.00', $w['output']['base']);
        $this->assertSame(2, $w['output']['count']);
        $this->assertSame('150.00', $w['net_payable']);
        foreach ($w['checks'] as $c) {
            $this->assertSame('0.00', $c['difference'], $c['label']);
        }
    }

    public function test_tax_codes_validate_rate_and_account(): void
    {
        $acct = Account::query()->where('code', '2300')->value('id');
        $code = app(TaxService::class)->saveCode($this->organization->id, ['code' => 'VAT-STD', 'name' => 'Standard VAT', 'kind' => 'vat_output', 'rate' => '5', 'account_id' => $acct]);
        $this->assertSame('5.0000', (string) $code->rate);
        foreach ([['rate' => '150'], ['rate' => 'abc'], ['kind' => 'bogus'], ['account_id' => 0], ['code' => 'VAT-STD']] as $bad) {
            try {
                app(TaxService::class)->saveCode($this->organization->id, $bad + ['code' => 'X1', 'name' => 'x', 'kind' => 'ait', 'rate' => '10', 'account_id' => $acct]);
                $this->fail('accepted '.json_encode($bad));
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_stock_movements_are_valued_per_day_and_posted_once(): void
    {
        $org = $this->organization->id;
        $item = InventoryItem::factory()->create(['organization_id' => $org, 'standard_cost' => '20.0000']);
        $row = fn (string $type, string $qty, string $at, ?string $cost = '20.0000', string $kind = 'central') => DB::table('stock_ledger')->insert(['organization_id' => $org, 'store_kind' => $kind, 'store_id' => 1, 'item_id' => $item->id, 'quantity' => $qty, 'unit_cost' => $cost, 'txn_type' => $type,
            'performed_at' => $at, 'idempotency_key' => uniqid('k', true), 'created_at' => now()]);
        $row('issue', '-10', '2026-09-08 05:00:00');
        $row('issue', '-5', '2026-09-08 07:00:00');
        $row('expiry_writeoff', '-2', '2026-09-08 08:00:00');
        $row('adjustment_gain', '1', '2026-09-08 09:00:00');
        $row('dispense', '-3', '2026-09-08 10:00:00', '40.0000', 'pharmacy');
        $row('transfer_out', '-4', '2026-09-08 11:00:00');
        $row('issue', '-1', '2026-09-08 12:00:00', null); // takes the item's standard cost
        $row('issue', '-100', '2026-09-10 11:00:00'); // today: the day is not over
        $result = app(StockPostingService::class)->postMovements($org);
        $this->assertSame(4, $result['journals']);
        $this->assertSame(0, $result['unvalued']);
        $this->assertSame('440.00', $this->bal('5200'), 'issues 16 x 20 plus pharmacy dispensing 3 x 40');
        $this->assertSame('20.00', $this->bal('5300'), 'write-off 40 against a gain of 20: a net cost of 20');
        $this->assertSame('-340.00', $this->bal('1320'));
        $this->assertSame('-120.00', $this->bal('1310'));
        $again = app(StockPostingService::class)->postMovements($org);
        $this->assertSame(['journals' => 0, 'rows' => 0, 'unvalued' => 0], $again, 'resumes after the last row; today is left for tomorrow');
    }
}
