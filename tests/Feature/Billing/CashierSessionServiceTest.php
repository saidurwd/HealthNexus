<?php

namespace Tests\Feature\Billing;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPaymentMethod;
use App\Models\Patient;
use App\Services\Billing\CashierSessionService;
use App\Services\Billing\InvoiceLifecycleService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CashierSessionServiceTest extends BillingTestCase
{
    private CashierSessionService $sessions;

    private BillingPaymentMethod $cashMethod;

    private BillingInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessions = app(CashierSessionService::class);

        $this->cashMethod = BillingPaymentMethod::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Cash', 'code' => 'CASH', 'type' => 'cash', 'is_active' => true,
        ]);

        $invoices = app(InvoiceService::class);
        $lifecycle = app(InvoiceLifecycleService::class);
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $category = BillingCategory::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Consultation', 'code' => 'CONSULT', 'is_active' => true,
        ]);

        $item = BillingItem::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'category_id' => $category->id, 'item_code' => 'OPD-1', 'item_type' => 'consultation',
            'name' => 'OPD Consultation', 'base_price' => '500.00', 'is_taxable' => false,
            'is_clinically_chargeable' => false, 'is_active' => true,
        ]);

        $charge = BillingCharge::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'patient_id' => $patient->id, 'billing_item_id' => $item->id,
            'idempotency_key' => uniqid('chg-', true), 'status' => 'pending', 'quantity' => 1,
            'currency' => 'BDT', 'unit_price' => '500.00', 'gross_amount' => '500.00',
            'discount_type' => 'none', 'discount_amount' => '0.00', 'tax_amount' => '0.00',
            'net_amount' => '500.00', 'charged_at' => now(),
        ]);

        $this->invoice = $invoices->createDraft($patient, ['company_id' => $this->company->id, 'branch_id' => $this->branch->id], $this->user);
        $invoices->addCharges($this->invoice, new Collection([$charge]));
        $this->invoice = $lifecycle->finalize($this->invoice, $this->user);
    }

    public function test_open_session_sets_expected_closing_to_opening_balance(): void
    {
        $session = $this->sessions->open($this->user, $this->company->id, $this->branch->id, '1000.00');

        $this->assertSame('open', $session->status);
        $this->assertSame('1000.00', (string) $session->opening_balance);
        $this->assertSame('1000.00', (string) $session->expected_closing);
    }

    public function test_user_cannot_open_a_second_session_while_one_is_already_open(): void
    {
        $this->sessions->open($this->user, $this->company->id, $this->branch->id, '1000.00');

        $this->expectException(ValidationException::class);

        $this->sessions->open($this->user, $this->company->id, $this->branch->id, '500.00');
    }

    public function test_close_computes_variance_between_expected_and_actual_cash(): void
    {
        $session = $this->sessions->open($this->user, $this->company->id, $this->branch->id, '1000.00');

        $payments = app(PaymentService::class);
        $payments->collect($this->invoice, [
            'payment_method_id' => $this->cashMethod->id, 'amount' => '500.00',
        ], $this->user, $session);

        // Expected: 1000 opening + 500 cash collected = 1500. Cashier counts 1490 -> variance -10.
        $closed = $this->sessions->close($session, '1490.00', 'short by 10', $this->user);

        $this->assertSame('closed', $closed->status);
        $this->assertSame('1500.00', (string) $closed->expected_closing);
        $this->assertSame('1490.00', (string) $closed->actual_closing);
        $this->assertSame('-10.00', (string) $closed->variance);
    }

    public function test_reconciliation_only_counts_cash_payments_not_card(): void
    {
        $cardMethod = BillingPaymentMethod::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Card', 'code' => 'CARD', 'type' => 'card', 'is_active' => true,
        ]);

        $session = $this->sessions->open($this->user, $this->company->id, $this->branch->id, '0.00');

        $payments = app(PaymentService::class);
        $payments->collect($this->invoice, [
            'payment_method_id' => $cardMethod->id, 'amount' => '500.00',
        ], $this->user, $session);

        $reconciliation = $this->sessions->reconcile($session);

        $this->assertSame('0.00', $reconciliation['expected_collections'], 'card payments must not count toward cash reconciliation');
    }
}
