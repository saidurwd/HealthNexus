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

/**
 * Regression coverage for the dashboard/report pages: these run raw joined SQL queries
 * (RevenueService) that a service-level unit test with mocked/empty data won't exercise
 * end-to-end. Seeds a real paid invoice so the join queries actually run against rows,
 * not just empty tables — this is what caught the "Column 'company_id' in WHERE is
 * ambiguous" bug in RevenueService::cashierSummary() (a join against
 * billing_payment_methods, which also has company_id/branch_id columns).
 */
class BillingDashboardAndReportsTest extends BillingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            'name' => 'OPD Consultation', 'base_price' => '500.00', 'is_taxable' => false,
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
            'currency' => 'BDT', 'unit_price' => '500.00', 'gross_amount' => '500.00',
            'discount_type' => 'none', 'discount_amount' => '0.00', 'tax_amount' => '0.00',
            'net_amount' => '500.00', 'charged_at' => now(),
        ]);

        $invoice = $invoices->createDraft($patient, ['company_id' => $this->company->id, 'branch_id' => $this->branch->id], $this->user);
        $invoices->addCharges($invoice, new Collection([$charge]));
        $invoice = $lifecycle->finalize($invoice, $this->user);

        $payments->collect($invoice, [
            'payment_method_id' => $method->id, 'amount' => '500.00',
        ], $this->user);
    }

    public function test_dashboard_loads_with_seeded_data(): void
    {
        $response = $this->get('/admin/billing');

        $response->assertOk();
        $response->assertViewHas('todaySummary');
        $response->assertViewHas('cashierSummary');
    }

    public function test_collection_report_loads(): void
    {
        $this->get('/admin/billing/reports/collection')->assertOk();
    }

    public function test_billing_report_loads(): void
    {
        $this->get('/admin/billing/reports/billing')->assertOk();
    }

    public function test_revenue_report_loads(): void
    {
        $this->get('/admin/billing/reports/revenue')->assertOk();
    }

    public function test_receivables_report_loads(): void
    {
        $this->get('/admin/billing/reports/receivables')->assertOk();
    }

    public function test_refunds_report_loads(): void
    {
        $this->get('/admin/billing/reports/refunds')->assertOk();
    }

    public function test_discounts_report_loads(): void
    {
        $this->get('/admin/billing/reports/discounts')->assertOk();
    }
}
