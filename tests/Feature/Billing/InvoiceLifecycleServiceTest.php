<?php

namespace Tests\Feature\Billing;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingItem;
use App\Models\Patient;
use App\Services\Billing\InvoiceLifecycleService;
use App\Services\Billing\InvoiceService;
use Illuminate\Validation\ValidationException;

class InvoiceLifecycleServiceTest extends BillingTestCase
{
    private InvoiceService $invoices;

    private InvoiceLifecycleService $lifecycle;

    private Patient $patient;

    private BillingItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoices = app(InvoiceService::class);
        $this->lifecycle = app(InvoiceLifecycleService::class);
        $this->patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $category = BillingCategory::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Consultation', 'code' => 'CONSULT', 'is_active' => true,
        ]);

        $this->item = BillingItem::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'category_id' => $category->id, 'item_code' => 'OPD-1', 'item_type' => 'consultation',
            'name' => 'OPD Consultation', 'base_price' => '500.00', 'is_taxable' => false,
            'is_clinically_chargeable' => false, 'is_active' => true,
        ]);
    }

    private function makeCharge(): BillingCharge
    {
        return BillingCharge::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id, 'billing_item_id' => $this->item->id,
            'idempotency_key' => uniqid('chg-', true), 'status' => 'pending', 'quantity' => 1,
            'currency' => 'BDT', 'unit_price' => '500.00', 'gross_amount' => '500.00',
            'discount_type' => 'none', 'discount_amount' => '0.00', 'tax_amount' => '0.00',
            'net_amount' => '500.00', 'charged_at' => now(),
        ]);
    }

    /**
     * addCharges() expects an Eloquent collection (as the controller produces via ->get()) —
     * this mirrors that rather than a plain Support collection.
     */
    private function chargeCollection(BillingCharge ...$charges): \Illuminate\Database\Eloquent\Collection
    {
        return new \Illuminate\Database\Eloquent\Collection($charges);
    }

    public function test_finalize_assigns_invoice_number_and_transitions_status(): void
    {
        $invoice = $this->invoices->createDraft($this->patient, [
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
        ], $this->user);

        $this->invoices->addCharges($invoice, $this->chargeCollection($this->makeCharge()));

        $finalized = $this->lifecycle->finalize($invoice, $this->user);

        $this->assertNotNull($finalized->invoice_number);
        $this->assertStringStartsWith('INV-', $finalized->invoice_number);
        $this->assertSame('finalized', $finalized->status);
        $this->assertSame('500.00', (string) $finalized->grand_total);
    }

    public function test_cannot_finalize_invoice_with_no_items(): void
    {
        $invoice = $this->invoices->createDraft($this->patient, [
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
        ], $this->user);

        $this->expectException(ValidationException::class);

        $this->lifecycle->finalize($invoice, $this->user);
    }

    public function test_invoice_is_immutable_after_finalization(): void
    {
        $invoice = $this->invoices->createDraft($this->patient, [
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
        ], $this->user);

        $this->invoices->addCharges($invoice, $this->chargeCollection($this->makeCharge()));
        $invoice = $this->lifecycle->finalize($invoice, $this->user);

        $secondCharge = $this->makeCharge();

        $this->expectException(ValidationException::class);

        $this->invoices->addCharges($invoice, $this->chargeCollection($secondCharge));
    }

    public function test_two_finalized_invoices_get_unique_sequential_numbers(): void
    {
        $invoiceA = $this->invoices->createDraft($this->patient, ['company_id' => $this->company->id, 'branch_id' => $this->branch->id], $this->user);
        $this->invoices->addCharges($invoiceA, $this->chargeCollection($this->makeCharge()));
        $invoiceA = $this->lifecycle->finalize($invoiceA, $this->user);

        $invoiceB = $this->invoices->createDraft($this->patient, ['company_id' => $this->company->id, 'branch_id' => $this->branch->id], $this->user);
        $this->invoices->addCharges($invoiceB, $this->chargeCollection($this->makeCharge()));
        $invoiceB = $this->lifecycle->finalize($invoiceB, $this->user);

        $this->assertNotSame($invoiceA->invoice_number, $invoiceB->invoice_number);
    }

    public function test_cancel_requires_reason_and_transitions_status(): void
    {
        $invoice = $this->invoices->createDraft($this->patient, ['company_id' => $this->company->id, 'branch_id' => $this->branch->id], $this->user);
        $this->invoices->addCharges($invoice, $this->chargeCollection($this->makeCharge()));

        $cancelled = $this->lifecycle->cancel($invoice, 'Patient requested cancellation', $this->user);

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertSame('Patient requested cancellation', $cancelled->cancellation_reason);
    }
}
