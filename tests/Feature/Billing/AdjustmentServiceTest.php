<?php

namespace Tests\Feature\Billing;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingItem;
use App\Models\Patient;
use App\Models\User;
use App\Services\Billing\AdjustmentService;
use App\Services\Billing\InvoiceLifecycleService;
use App\Services\Billing\InvoiceService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class AdjustmentServiceTest extends BillingTestCase
{
    private AdjustmentService $adjustments;

    private BillingInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adjustments = app(AdjustmentService::class);

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
    }

    public function test_discount_adjustment_only_applies_to_invoice_after_approval(): void
    {
        $approver = User::factory()->create();
        $approver->companies()->attach($this->company->id, ['access_level' => 'admin']);

        $adjustment = $this->adjustments->request($this->invoice, 'discount', '0', '100', 'Loyalty discount', $this->user);

        $this->invoice->refresh();
        $this->assertSame('1000.00', (string) $this->invoice->grand_total, 'grand total must be unchanged before approval');

        $this->adjustments->approve($adjustment, $approver);

        $this->invoice->refresh();
        $this->assertSame('100.00', (string) $this->invoice->discount_amount);
        $this->assertSame('900.00', (string) $this->invoice->grand_total);
        $this->assertSame('900.00', (string) $this->invoice->due_amount);
    }

    public function test_write_off_adjustment_zeroes_due_amount_and_sets_status(): void
    {
        $approver = User::factory()->create();
        $approver->companies()->attach($this->company->id, ['access_level' => 'admin']);

        $adjustment = $this->adjustments->request($this->invoice, 'write_off', (string) $this->invoice->grand_total, '0', 'Uncollectable', $this->user);
        $this->adjustments->approve($adjustment, $approver);

        $this->invoice->refresh();
        $this->assertSame('written_off', $this->invoice->status);
        $this->assertSame('0.00', (string) $this->invoice->due_amount);
    }

    public function test_cannot_approve_an_already_approved_adjustment(): void
    {
        $approver = User::factory()->create();
        $approver->companies()->attach($this->company->id, ['access_level' => 'admin']);

        $adjustment = $this->adjustments->request($this->invoice, 'discount', '0', '50', 'Discount', $this->user);
        $this->adjustments->approve($adjustment, $approver);

        $this->expectException(ValidationException::class);

        $this->adjustments->approve($adjustment, $approver);
    }

    public function test_large_discount_requires_approval_per_threshold(): void
    {
        // default threshold: 10% or 5000 absolute — a 20% discount on a 1000 invoice (200) exceeds 10%
        $adjustment = $this->adjustments->request($this->invoice, 'discount', '0', '200', 'Big discount', $this->user);

        $this->assertTrue($this->adjustments->requiresApproval($adjustment));
    }

    public function test_small_discount_does_not_require_approval_per_threshold(): void
    {
        // 1% of 1000 = 10, well under both thresholds
        $adjustment = $this->adjustments->request($this->invoice, 'discount', '0', '10', 'Small discount', $this->user);

        $this->assertFalse($this->adjustments->requiresApproval($adjustment));
    }
}
