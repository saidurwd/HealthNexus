<?php

namespace Tests\Feature\Billing;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPaymentMethod;
use App\Models\Patient;
use App\Services\Billing\InvoiceLifecycleService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PaymentServiceTest extends BillingTestCase
{
    private PaymentService $payments;

    private BillingInvoice $invoice;

    private BillingPaymentMethod $cashMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payments = app(PaymentService::class);
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
            'name' => 'OPD Consultation', 'base_price' => '1000.00', 'is_taxable' => false,
            'is_clinically_chargeable' => false, 'is_active' => true,
        ]);

        $this->cashMethod = BillingPaymentMethod::create([
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

        $this->invoice = $invoices->createDraft($patient, [
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
        ], $this->user);

        $invoices->addCharges($this->invoice, new Collection([$charge]));
        $this->invoice = $lifecycle->finalize($this->invoice, $this->user);
    }

    public function test_partial_payment_updates_invoice_status_and_generates_receipt(): void
    {
        $payment = $this->payments->collect($this->invoice, [
            'payment_method_id' => $this->cashMethod->id,
            'amount' => '400.00',
        ], $this->user);

        $this->invoice->refresh();

        $this->assertSame('400.00', (string) $this->invoice->paid_amount);
        $this->assertSame('600.00', (string) $this->invoice->due_amount);
        $this->assertSame('partially_paid', $this->invoice->status);
        $this->assertNotNull($payment->receipt);
        $this->assertStringStartsWith('RCT-', $payment->receipt->receipt_number);
    }

    public function test_full_payment_across_two_partial_payments_marks_invoice_paid(): void
    {
        $this->payments->collect($this->invoice, [
            'payment_method_id' => $this->cashMethod->id,
            'amount' => '400.00',
        ], $this->user);

        $this->payments->collect($this->invoice, [
            'payment_method_id' => $this->cashMethod->id,
            'amount' => '600.00',
        ], $this->user);

        $this->invoice->refresh();

        $this->assertSame('1000.00', (string) $this->invoice->paid_amount);
        $this->assertSame('0.00', (string) $this->invoice->due_amount);
        $this->assertSame('paid', $this->invoice->status);
        $this->assertSame(2, $this->invoice->payments()->count());
    }

    public function test_payment_exceeding_due_amount_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->payments->collect($this->invoice, [
            'payment_method_id' => $this->cashMethod->id,
            'amount' => '2000.00',
        ], $this->user);
    }

    public function test_each_payment_is_a_new_row_never_a_mutation_of_a_prior_one(): void
    {
        $first = $this->payments->collect($this->invoice, [
            'payment_method_id' => $this->cashMethod->id,
            'amount' => '400.00',
        ], $this->user);

        $second = $this->payments->collect($this->invoice, [
            'payment_method_id' => $this->cashMethod->id,
            'amount' => '600.00',
        ], $this->user);

        $this->assertNotSame($first->id, $second->id);
        $first->refresh();
        $this->assertSame('400.00', (string) $first->amount);
    }

    public function test_completed_payment_cannot_be_cancelled(): void
    {
        $payment = $this->payments->collect($this->invoice, [
            'payment_method_id' => $this->cashMethod->id,
            'amount' => '400.00',
        ], $this->user);

        $this->expectException(ValidationException::class);

        $this->payments->cancel($payment, 'changed my mind', $this->user);
    }
}
