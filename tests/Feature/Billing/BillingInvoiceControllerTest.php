<?php

namespace Tests\Feature\Billing;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPaymentMethod;
use App\Models\Patient;

class BillingInvoiceControllerTest extends BillingTestCase
{
    private Patient $patient;

    private BillingItem $item;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_user_can_view_invoice_index(): void
    {
        $response = $this->get('/admin/billing/invoices');

        $response->assertOk();
        $response->assertViewHas('invoices');
    }

    public function test_user_can_create_a_draft_invoice(): void
    {
        $response = $this->post('/admin/billing/invoices', [
            'patient_id' => $this->patient->id,
            'invoice_type' => 'opd',
        ]);

        $invoice = BillingInvoice::first();
        $response->assertRedirect('/admin/billing/invoices/'.$invoice->id);
        $this->assertSame('draft', $invoice->status);
        $this->assertNull($invoice->invoice_number);
    }

    public function test_full_invoice_to_payment_happy_path(): void
    {
        // 1. Create draft invoice.
        $this->post('/admin/billing/invoices', [
            'patient_id' => $this->patient->id,
            'invoice_type' => 'opd',
        ]);
        $invoice = BillingInvoice::first();

        // 2. Add a manually-created charge to it.
        $charge = BillingCharge::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id, 'billing_item_id' => $this->item->id,
            'idempotency_key' => uniqid('chg-', true), 'status' => 'pending', 'quantity' => 1,
            'currency' => 'BDT', 'unit_price' => '500.00', 'gross_amount' => '500.00',
            'discount_type' => 'none', 'discount_amount' => '0.00', 'tax_amount' => '0.00',
            'net_amount' => '500.00', 'charged_at' => now(),
        ]);

        $this->post("/admin/billing/invoices/{$invoice->id}/charges", [
            'charge_ids' => [$charge->id],
        ])->assertRedirect("/admin/billing/invoices/{$invoice->id}");

        $invoice->refresh();
        $this->assertSame('500.00', (string) $invoice->grand_total);

        // 3. Finalize.
        $this->post("/admin/billing/invoices/{$invoice->id}/finalize")
            ->assertRedirect("/admin/billing/invoices/{$invoice->id}");

        $invoice->refresh();
        $this->assertSame('finalized', $invoice->status);
        $this->assertNotNull($invoice->invoice_number);

        // 4. Collect payment.
        $method = BillingPaymentMethod::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Cash', 'code' => 'CASH', 'type' => 'cash', 'is_active' => true,
        ]);

        $this->post('/admin/billing/payments', [
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'amount' => '500.00',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame('0.00', (string) $invoice->due_amount);

        // 5. A receipt should exist for the payment.
        $payment = $invoice->payments()->first();
        $this->assertNotNull($payment->receipt);
    }

    public function test_view_invoice_show_page(): void
    {
        $this->post('/admin/billing/invoices', [
            'patient_id' => $this->patient->id,
            'invoice_type' => 'opd',
        ]);
        $invoice = BillingInvoice::first();

        $response = $this->get('/admin/billing/invoices/'.$invoice->id);

        $response->assertOk();
        $response->assertViewHas('invoice');
    }
}
