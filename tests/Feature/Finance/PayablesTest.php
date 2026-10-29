<?php

namespace Tests\Feature\Finance;

use App\Integrations\Finance\LedgerFinanceGateway;
use App\Models\Finance\Account;
use App\Models\Finance\Journal;
use App\Models\Finance\Payable;
use App\Models\Finance\TaxEntry;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\ProcurementGoodsReceipt;
use App\Models\Inventory\ProcurementGrnLine;
use App\Models\Inventory\ProcurementPoLine;
use App\Models\Inventory\ProcurementPurchaseOrder;
use App\Models\Inventory\ProcurementSupplier;
use App\Models\Inventory\ProcurementSupplierInvoice;
use App\Services\Finance\BankService;
use App\Services\Finance\LedgerService;
use App\Services\Finance\PayableService;
use App\Services\Finance\StockPostingService;
use Illuminate\Validation\ValidationException;

class PayablesTest extends FinanceTestCase
{
    private function bal(string $code): string
    {
        return app(LedgerService::class)->balance($this->organization->id, Account::query()->where(['organization_id' => $this->organization->id, 'code' => $code])->first());
    }

    private function purchase(string $subtotal = '10000.00', string $tax = '1500.00'): array
    {
        $org = $this->organization->id;
        $supplier = ProcurementSupplier::factory()->create(['organization_id' => $org, 'bin_number' => '000123456-0101', 'payment_terms_days' => 30]);
        $store = InventoryStore::factory()->create(['organization_id' => $org, 'kind' => 'central']);
        $item = InventoryItem::factory()->create(['organization_id' => $org]);
        $po = ProcurementPurchaseOrder::create(['organization_id' => $org, 'branch_id' => $this->branch->id, 'supplier_id' => $supplier->id, 'store_id' => $store->id, 'number' => 'PO-'.uniqid(), 'po_type' => 'standard', 'status' => 'issued', 'order_date' => '2026-09-01', 'subtotal' => $subtotal, 'tax_amount' => $tax, 'total' => bcadd($subtotal, $tax, 2), 'created_by' => $this->superAdmin()->id]);
        $poLine = ProcurementPoLine::create(['purchase_order_id' => $po->id, 'item_id' => $item->id, 'quantity' => 100, 'unit_price' => bcdiv($subtotal, '100', 2), 'tax_percent' => 15, 'line_total' => $subtotal]);
        $grn = ProcurementGoodsReceipt::create(['organization_id' => $org, 'purchase_order_id' => $po->id, 'supplier_id' => $supplier->id, 'store_id' => $store->id, 'number' => 'GRN-'.uniqid(), 'received_on' => '2026-09-03', 'status' => 'posted', 'posted_at' => '2026-09-03 08:00:00', 'received_by' => $this->superAdmin()->id]);
        ProcurementGrnLine::create(['goods_receipt_id' => $grn->id, 'po_line_id' => $poLine->id, 'item_id' => $item->id, 'received_quantity' => 100, 'accepted_quantity' => 100, 'rejected_quantity' => 0, 'unit_cost' => bcdiv($subtotal, '100', 4)]);
        $invoice = ProcurementSupplierInvoice::create(['organization_id' => $org, 'supplier_id' => $supplier->id, 'purchase_order_id' => $po->id, 'invoice_number' => 'SI-'.uniqid(), 'invoice_date' => '2026-09-05', 'due_on' => '2026-10-05', 'subtotal' => $subtotal, 'tax_amount' => $tax,
            'total' => bcadd($subtotal, $tax, 2), 'status' => 'matched', 'recorded_by' => $this->superAdmin()->id]);

        return [$supplier, $grn, $invoice];
    }

    public function test_goods_received_then_supplier_invoice_then_payment_chain_posts_correctly(): void
    {
        [$supplier, $grn, $invoice] = $this->purchase();
        $this->assertSame(1, app(StockPostingService::class)->postReceipts($this->organization->id));
        $this->assertSame(0, app(StockPostingService::class)->postReceipts($this->organization->id), 'a receipt posts once');
        $this->assertSame('10000.00', $this->bal('1320'), 'inventory at cost');
        $this->assertSame('10000.00', $this->bal('2150'), 'received, not yet invoiced');

        $payable = app(PayableService::class)->fromSupplierInvoice($invoice);
        $this->assertSame($payable->id, app(PayableService::class)->fromSupplierInvoice($invoice)->id, 'posted once');
        $this->assertSame('0.00', $this->bal('2150'), 'the invoice clears what was received');
        $this->assertSame('1500.00', $this->bal('1450'), 'VAT input');
        $this->assertSame('11500.00', $this->bal('2100'), 'owed to suppliers');
        $this->assertSame(1, TaxEntry::query()->where(['kind' => 'vat_input', 'source_id' => (string) $invoice->id])->count());
        $this->assertSame('2026-10-05', $payable->due_date->toDateString());

        $bank = app(BankService::class)->createAccount($this->organization->id, 'City Bank current', 'bank', Account::query()->where('code', '1120')->value('id'), 'City Bank', '4321');
        $maker = $this->accountant();
        $payment = app(PayableService::class)->createPayment($this->organization->id, ['supplier_id' => $supplier->id, 'payment_date' => '2026-09-08', 'method' => 'bank', 'bank_account_id' => $bank->id, 'withheld_ait' => '500.00', 'withheld_vat' => '0'], [['payable_id' => $payable->id, 'amount' => '11500.00']], $maker);
        $this->assertSame('11000.00', (string) $payment->net_amount);
        try {
            app(PayableService::class)->approvePayment($payment, $maker);
            $this->fail('self approval');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('approver', $e->errors());
        }
        try {
            app(PayableService::class)->pay($payment, $maker);
            $this->fail('paid before approval');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $payment = app(PayableService::class)->approvePayment($payment->refresh(), $this->accountant());
        $paid = app(PayableService::class)->pay($payment, $this->accountant());
        $this->assertSame('paid', $paid->status);
        $this->assertSame('0.00', $this->bal('2100'));
        $this->assertSame('-11000.00', $this->bal('1120'), 'the bank had no money booked in this test, so paying shows as an overdraft');
        $this->assertSame('500.00', $this->bal('2310'), 'AIT withheld and owed to the tax authority');
        $this->assertSame('settled', $payable->refresh()->status);
        $this->assertSame(1, TaxEntry::query()->where(['kind' => 'ait', 'source_id' => (string) $paid->id])->count());
        $this->assertTrue(app(LedgerService::class)->totals(app(LedgerService::class)->trialBalance($this->organization->id, null, '2026-12-31'))['balanced']);

        $back = app(PayableService::class)->reversePayment($paid, $this->accountant(), 'Wrong supplier account');
        $this->assertSame('cancelled', $back->status);
        $this->assertSame('11500.00', $this->bal('2100'));
        $this->assertSame('open', $payable->refresh()->status);
    }

    public function test_payment_rules(): void
    {
        [$supplier, , $invoice] = $this->purchase('1000.00', '0.00');
        $payable = app(PayableService::class)->fromSupplierInvoice($invoice);
        $bank = app(BankService::class)->createAccount($this->organization->id, 'Till', 'cash', Account::query()->where('code', '1110')->value('id'));
        $svc = app(PayableService::class);
        $make = fn (array $alloc, array $extra = []) => $svc->createPayment($this->organization->id, ['supplier_id' => $supplier->id, 'payment_date' => '2026-09-08', 'method' => 'cash', 'bank_account_id' => $bank->id] + $extra, $alloc, $this->accountant());
        foreach ([
            fn () => $make([['payable_id' => $payable->id, 'amount' => '1000.01']]),
            fn () => $make([['payable_id' => $payable->id, 'amount' => '0']]),
            fn () => $make([]),
            fn () => $make([['payable_id' => $payable->id, 'amount' => '100.00']], ['withheld_ait' => '150.00']),
            fn () => $make([['payable_id' => $payable->id, 'amount' => '100.00']], ['withheld_ait' => '-1']),
            fn () => $svc->createPayment($this->organization->id, ['supplier_id' => $supplier->id + 99, 'payment_date' => '2026-09-08', 'method' => 'cash', 'bank_account_id' => $bank->id], [['payable_id' => $payable->id, 'amount' => '10']], $this->accountant()),
        ] as $bad) {
            try {
                $bad();
                $this->fail('accepted a bad payment');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $part = $make([['payable_id' => $payable->id, 'amount' => '400.00']]);
        $svc->pay($svc->approvePayment($part, $this->accountant()), $this->accountant());
        $this->assertSame('open', $payable->refresh()->status);
        $this->assertSame('600.00', $svc->outstanding($payable));
        $age = $svc->ageing($this->organization->id, '2026-11-01');
        $this->assertSame('600.00', $age['total']);
        $this->assertSame('600.00', $age['rows'][0]['buckets'][1], 'due 5 Oct, 27 days late at 1 Nov');
    }

    public function test_finance_gateway_takes_a_supplier_invoice_and_says_not_enabled_when_off(): void
    {
        [, , $invoice] = $this->purchase('2000.00', '0.00');
        $gateway = app(LedgerFinanceGateway::class);
        $result = $gateway->postPayable(['organization_id' => $this->organization->id, 'source' => 'inventory:'.$invoice->public_id]);
        $this->assertTrue($result->handled);
        $this->assertSame(1, Payable::query()->count());
        $this->assertSame('2000.00', $this->bal('2100'));
        $this->assertFalse($gateway->postPayable(['organization_id' => 999999, 'source' => 'x'])->handled);
        $this->assertSame(1, Journal::query()->where('source_type', 'supplier_invoice')->count());
    }

    public function test_payroll_journal_goes_through_the_gateway_with_role_names_and_posts_once(): void
    {
        $lines = [['account' => 'SALARY_EXPENSE', 'debit' => '1000.00', 'credit' => '0.00'], ['account' => 'SALARY_PAYABLE', 'debit' => '0.00', 'credit' => '900.00'], ['account' => 'TAX_PAYABLE', 'debit' => '0.00', 'credit' => '100.00']];
        $data = ['organization_id' => $this->organization->id, 'source' => 'hr:payroll:abc', 'date' => '2026-09-05', 'memo' => 'Payroll', 'lines' => $lines];
        $a = app(LedgerFinanceGateway::class)->postJournal($data);
        $b = app(LedgerFinanceGateway::class)->postJournal($data);
        $this->assertTrue($a->handled);
        $this->assertSame($a->data['reference'], $b->data['reference']);
        $this->assertSame('1000.00', $this->bal('5100'));
        $this->assertSame('900.00', $this->bal('2320'));
        $this->assertSame('100.00', $this->bal('2350'));
        $unmapped = app(LedgerFinanceGateway::class)->postJournal(['lines' => [['account' => 'NO_SUCH', 'debit' => '1', 'credit' => '0'], ['account' => 'SALARY_PAYABLE', 'debit' => '0', 'credit' => '1']]] + $data + ['source' => 'x']);
        $this->assertFalse($unmapped->handled);
    }
}
