<?php

namespace Tests\Feature\Billing;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingPaymentMethod;
use App\Models\Patient;
use App\Models\User;
use App\Services\Billing\InvoiceLifecycleService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use App\Services\Billing\RefundService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class RefundServiceTest extends BillingTestCase
{
    private RefundService $refunds;

    private BillingPayment $payment;

    private BillingInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refunds = app(RefundService::class);

        $invoices = app(InvoiceService::class);
        $lifecycle = app(InvoiceLifecycleService::class);
        $payments = app(PaymentService::class);

        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $category = BillingCategory::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Consultation', 'code' => 'CONSULT', 'is_active' => true,
        ]);

        $item = BillingItem::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'category_id' => $category->id, 'item_code' => 'OPD-1', 'item_type' => 'consultation',
            'name' => 'OPD Consultation', 'base_price' => '1000.00', 'is_taxable' => false,
            'is_clinically_chargeable' => false, 'is_active' => true,
        ]);

        $method = BillingPaymentMethod::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Cash', 'code' => 'CASH', 'type' => 'cash', 'is_active' => true,
        ]);

        $charge = BillingCharge::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'patient_id' => $patient->id, 'billing_item_id' => $item->id,
            'idempotency_key' => uniqid('chg-', true), 'status' => 'pending', 'quantity' => 1,
            'currency' => 'BDT', 'unit_price' => '1000.00', 'gross_amount' => '1000.00',
            'discount_type' => 'none', 'discount_amount' => '0.00', 'tax_amount' => '0.00',
            'net_amount' => '1000.00', 'charged_at' => now(),
        ]);

        $this->invoice = $invoices->createDraft($patient, ['company_id' => $this->company->id, 'branch_id' => $this->branch->id], $this->user);
        $invoices->addCharges($this->invoice, new Collection([$charge]));
        $this->invoice = $lifecycle->finalize($this->invoice, $this->user);

        $this->payment = $payments->collect($this->invoice, [
            'payment_method_id' => $method->id, 'amount' => '1000.00',
        ], $this->user);
    }

    public function test_full_refund_workflow_moves_through_requested_approved_processed(): void
    {
        $approver = User::factory()->create();
        $approver->companies()->attach($this->company->id, ['access_level' => 'admin']);

        $refund = $this->refunds->request($this->payment, '1000.00', 'Patient overcharged', $this->user);
        $this->assertSame('requested', $refund->status);

        $refund = $this->refunds->approve($refund, $approver);
        $this->assertSame('approved', $refund->status);

        $refund = $this->refunds->process($refund, $approver, 'REF-REF-001');
        $this->assertSame('processed', $refund->status);

        $this->payment->refresh();
        $this->assertSame('refunded', $this->payment->status);

        $this->invoice->refresh();
        $this->assertSame('0.00', (string) $this->invoice->paid_amount);

        $this->assertSame('voided', $this->payment->receipt->refresh()->status);
    }

    public function test_partial_refund_leaves_the_receipt_issued(): void
    {
        $approver = User::factory()->create();
        $approver->companies()->attach($this->company->id, ['access_level' => 'admin']);

        $refund = $this->refunds->request($this->payment, '400.00', 'Partial refund', $this->user);
        $refund = $this->refunds->approve($refund, $approver);
        $this->refunds->process($refund, $approver);

        $this->assertSame('issued', $this->payment->receipt->refresh()->status);
    }

    public function test_refund_cannot_be_approved_by_the_user_who_requested_it(): void
    {
        $refund = $this->refunds->request($this->payment, '500.00', 'Goodwill refund', $this->user);

        $this->expectException(ValidationException::class);

        $this->refunds->approve($refund, $this->user);
    }

    public function test_refund_amount_cannot_exceed_remaining_refundable_amount(): void
    {
        $this->expectException(ValidationException::class);

        $this->refunds->request($this->payment, '1500.00', 'Too much', $this->user);
    }

    public function test_original_payment_amount_is_never_mutated_by_a_refund(): void
    {
        $approver = User::factory()->create();
        $approver->companies()->attach($this->company->id, ['access_level' => 'admin']);

        $refund = $this->refunds->request($this->payment, '400.00', 'Partial refund', $this->user);
        $refund = $this->refunds->approve($refund, $approver);
        $this->refunds->process($refund, $approver);

        $this->payment->refresh();
        $this->assertSame('1000.00', (string) $this->payment->amount);
    }
}
