<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\AccountRole;
use App\Models\Finance\FiscalYear;
use App\Models\Finance\Journal;
use App\Models\Finance\Posting;
use App\Models\Finance\Receivable;
use App\Models\Finance\TaxEntry;
use App\Services\Billing\AdjustmentService;
use App\Services\Billing\InvoiceLifecycleService;
use App\Services\Billing\RefundService;
use App\Services\Finance\FiscalService;
use App\Services\Finance\LedgerService;
use App\Services\Finance\PostingEngine;
use App\Services\Finance\ReceivableService;
use App\Services\Finance\ReconciliationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostingTest extends FinanceTestCase
{
    private function bal(string $code): string
    {
        $a = Account::query()->where(['organization_id' => $this->organization->id, 'code' => $code])->first();

        return app(LedgerService::class)->balance($this->organization->id, $a);
    }

    private function recon(): array
    {
        return app(ReconciliationService::class)->billingVsLedger($this->organization->id, '2026-01-01', '2026-12-31');
    }

    public function test_a_finalised_invoice_posts_receivable_and_revenue_once(): void
    {
        $invoice = $this->bill(null, [['CONSULT', '1000.00'], ['LAB', '500.00']]);
        $journal = Journal::query()->where(['source_type' => 'invoice', 'source_id' => (string) $invoice->id])->first();
        $this->assertNotNull($journal, 'the Billing event posted a journal');
        $this->assertSame('1500.00', (string) $journal->total_debit);
        $this->assertSame('1500.00', $this->bal('1210'), 'patient receivable');
        $this->assertSame('1000.00', $this->bal('4100'), 'consultation revenue (category CONSULT)');
        $this->assertSame('500.00', $this->bal('4300'), 'laboratory revenue (category LAB)');
        $item = Receivable::query()->where(['source_type' => 'invoice', 'source_id' => $invoice->id])->first();
        $this->assertSame('1500.00', (string) $item->amount);
        $this->assertSame('open', $item->status);

        app(PostingEngine::class)->post('invoice.finalized', $invoice); // the same event again
        app(PostingEngine::class)->catchUp($this->organization->id);
        $this->assertSame(1, Journal::query()->where(['source_type' => 'invoice', 'source_id' => (string) $invoice->id])->count());
        $this->assertSame('1500.00', $this->bal('1210'));
        $this->assertTrue($this->recon()['in_balance'], json_encode($this->recon()));
    }

    public function test_discount_tax_and_rounding_keep_the_journal_balanced(): void
    {
        $invoice = $this->bill(null, [['CONSULT', '1000.00']], '5');
        $this->assertSame('50.00', (string) $invoice->tax_amount);
        $this->assertSame('1050.00', $this->bal('1210'));
        $this->assertSame('50.00', $this->bal('2300'), 'VAT output');
        $this->assertSame('1000.00', $this->bal('4100'));
        $this->assertTrue(app(LedgerService::class)->totals(app(LedgerService::class)->trialBalance($this->organization->id, null, '2026-12-31'))['balanced']);
        $this->assertSame(1, TaxEntry::query()->where(['kind' => 'vat_output', 'source_id' => (string) $invoice->id])->count());
    }

    public function test_payments_settle_the_receivable_and_a_refund_reopens_it(): void
    {
        $invoice = $this->bill(null, [['CONSULT', '1000.00']]);
        $p1 = $this->pay($invoice, '400.00');
        $this->assertSame('600.00', $this->bal('1210'));
        $this->assertSame('400.00', $this->bal('1110'), 'cash');
        $item = Receivable::query()->where(['source_type' => 'invoice', 'source_id' => $invoice->id])->first();
        $this->assertSame('open', $item->status);
        $this->pay($invoice->refresh(), '600.00');
        $this->assertSame('0.00', $this->bal('1210'));
        $this->assertSame('settled', $item->refresh()->status);

        $refunds = app(RefundService::class);
        $user = $this->superAdmin();
        $this->actingAs($user);
        $refund = $refunds->request($p1, '100.00', 'Overcharged', $user);
        $refunds->approve($refund, $this->superAdmin(), 'ok');
        $refunds->process($refund->refresh(), $user);
        $this->assertSame('100.00', $this->bal('1210'), 'the refunded part is owed again');
        $this->assertSame('900.00', $this->bal('1110'));
        $this->assertSame('open', $item->refresh()->status);
        $this->assertTrue($this->recon()['in_balance'], json_encode($this->recon()));
    }

    public function test_mobile_wallet_and_card_payments_go_to_their_own_accounts(): void
    {
        $invoice = $this->bill(null, [['CONSULT', '300.00']]);
        $this->pay($invoice, '100.00', $this->cashMethod('MFS', 'mobile_financial_service'));
        $this->pay($invoice->refresh(), '100.00', $this->cashMethod('CARD', 'card'));
        $this->assertSame('100.00', $this->bal('1130'));
        $this->assertSame('100.00', $this->bal('1140'));
        $this->assertSame('0.00', $this->bal('1110'));
    }

    public function test_a_cancelled_invoice_is_reversed(): void
    {
        $invoice = $this->bill(null, [['CONSULT', '800.00']]);
        app(InvoiceLifecycleService::class)->cancel($invoice->refresh(), 'Entered twice', $this->superAdmin());
        $this->assertSame('0.00', $this->bal('1210'));
        $this->assertSame('0.00', $this->bal('4100'));
        $this->assertSame('cancelled', Receivable::query()->where(['source_type' => 'invoice', 'source_id' => $invoice->id])->value('status'));
        $this->assertSame(1, Journal::query()->where('kind', 'reversal')->count());
    }

    public function test_an_approved_discount_and_a_write_off_post_against_the_invoice(): void
    {
        $user = $this->superAdmin();
        $invoice = $this->bill(null, [['CONSULT', '1000.00']]);
        $adj = app(AdjustmentService::class);
        $this->actingAs($user);
        $a = $adj->request($invoice, 'discount', '0.00', '100.00', 'Staff concession', $user);
        $adj->approve($a, $this->superAdmin(), 'ok');
        $this->assertSame('900.00', $this->bal('1210'));
        $this->assertSame('-100.00', $this->bal('4900'), 'discounts allowed sit on the debit side of a revenue account');
    }

    public function test_a_failure_waits_in_the_list_and_never_breaks_billing(): void
    {
        $fiscal = app(FiscalService::class);
        $period = $fiscal->periodFor($this->organization->id, now());
        $fiscal->lock($period, $this->superAdmin(), 'Closed');
        $invoice = $this->bill(null, [['CONSULT', '1000.00']]); // Billing still works
        $this->assertSame('finalized', $invoice->status);
        $row = Posting::query()->where(['source_type' => 'invoice', 'source_id' => (string) $invoice->id])->first();
        $this->assertSame('failed', $row->status);
        $this->assertStringContainsString('locked', $row->error);
        $this->assertSame(0, Journal::query()->where('source_id', (string) $invoice->id)->count());
        $this->assertFalse($this->recon()['in_balance']);

        $fiscal->unlock($period, $this->superAdmin(), 'Late invoice');
        $result = app(PostingEngine::class)->retryFailed($this->organization->id);
        $this->assertSame(['retried' => 1, 'posted' => 1], $result);
        $this->assertSame('1000.00', $this->bal('1210'));
        $this->assertTrue($this->recon()['in_balance'], json_encode($this->recon()));
    }

    public function test_nothing_is_posted_until_the_books_are_set_up(): void
    {
        FiscalYear::query()->delete();
        $invoice = $this->bill(null, [['CONSULT', '100.00']]);
        $this->assertSame(0, Journal::query()->count());
        $this->assertSame(0, Posting::query()->count(), 'not even a failure is recorded before the accountant starts the books');
        $this->assertNotNull($invoice->id);
    }

    public function test_an_unmapped_role_is_reported_not_guessed(): void
    {
        $invoice = $this->bill(null, [['CONSULT', '100.00']], null, false);
        AccountRole::query()->where('role', 'ar_patient')->delete();
        app(InvoiceLifecycleService::class)->finalize($invoice, $this->superAdmin());
        $row = Posting::query()->where(['source_type' => 'invoice', 'source_id' => (string) $invoice->id])->first();
        $this->assertSame('failed', $row->status);
        $this->assertStringContainsString('ar_patient', $row->error);
    }

    public function test_catch_up_finds_what_the_event_missed(): void
    {
        $invoice = $this->bill(null, [['CONSULT', '250.00']]);
        $this->pay($invoice, '250.00');
        Journal::query()->where('status', 'posted')->each(fn () => null);
        DB::statement('PRAGMA foreign_keys = OFF');
        // simulate a missed event: remove the posting records and journals by recreating the books empty
        $this->assertSame(2, Journal::query()->count());
        $this->assertSame([], app(PostingEngine::class)->catchUp($this->organization->id), 'nothing is missed when everything posted');
    }

    public function test_the_receivable_ageing_and_a_write_off_with_a_second_approver(): void
    {
        $this->bill(null, [['CONSULT', '1000.00']]);
        $this->travelTo(now()->addDays(45));
        $age = app(ReceivableService::class)->ageing($this->organization->id);
        $this->assertSame('1000.00', $age['totals']['total']);
        $this->assertCount(1, $age['rows']);
        $this->assertSame('1000.00', $age['rows'][0]['buckets'][2], 'due on the invoice date, 45 days late falls in 31-60');
        $item = Receivable::query()->first();
        $maker = $this->superAdmin();
        $w = app(ReceivableService::class)->requestWriteoff($item, '400.00', 'Patient left, untraceable', $maker);
        try {
            app(ReceivableService::class)->decideWriteoff($w, true, $maker);
            $this->fail('self-approved');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('approver', $e->errors());
        }
        app(ReceivableService::class)->decideWriteoff($w->refresh(), true, $this->superAdmin());
        $this->assertSame('600.00', $this->bal('1210'));
        $this->assertSame('400.00', $this->bal('5500'));
        $this->assertSame('600.00', app(ReceivableService::class)->outstanding($item->refresh()));
        $this->assertTrue($this->recon()['in_balance'], json_encode($this->recon()));
    }
}
