<?php

namespace Tests\Feature\Finance;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPaymentMethod;
use App\Models\Billing\BillingTaxCategory;
use App\Models\Finance\FiscalYear;
use App\Models\Finance\Journal;
use App\Models\Patient;
use App\Models\User;
use App\Services\Billing\InvoiceLifecycleService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use App\Services\Finance\ChartService;
use App\Services\Finance\FiscalService;
use App\Services\Finance\JournalService;
use Illuminate\Database\Eloquent\Collection;
use Tests\Feature\Portal\PortalTestCase;

/** The same hospital, branch, patients and invoices as the portal tests, plus a starter chart and the fiscal year 2026 (today is 10 September 2026). */
abstract class FinanceTestCase extends PortalTestCase
{
    protected FiscalYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        app(ChartService::class)->seedStarter($this->organization->id);
        $this->year = app(FiscalService::class)->createYear($this->organization->id, 2026, 1);
    }

    protected function chart(): ChartService
    {
        return app(ChartService::class);
    }

    protected function journals(): JournalService
    {
        return app(JournalService::class);
    }

    /** A posted two-line journal: debit one account, credit another. */
    protected function postJournal(string $debit, string $credit, string $amount, string $date = '2026-09-05', string $memo = 'Test'): Journal
    {
        return $this->journals()->postAuto($this->organization->id, ['date' => $date, 'memo' => $memo, 'source_type' => 'test', 'source_id' => uniqid(), 'event_key' => 'x'], [
            ['account' => $debit, 'debit' => $amount, 'credit' => '0'], ['account' => $credit, 'debit' => '0', 'credit' => $amount],
        ]);
    }

    protected function accountant(): User
    {
        return $this->superAdmin();
    }

    protected function cashMethod(string $code = 'CASH', string $type = 'cash'): BillingPaymentMethod
    {
        return BillingPaymentMethod::query()->firstOrCreate(['organization_id' => $this->organization->id, 'code' => $code], ['name' => $code, 'type' => $type, 'is_active' => true, 'branch_id' => null]);
    }

    /**
     * A finalised invoice made through the real Billing services: one charge per [category code, price]; $taxRate puts that percent VAT on every line.
     *
     * @param  list<array{0:string,1:string}>  $lines
     */
    protected function bill(?Patient $patient = null, array $lines = [['CONSULT', '1000.00']], ?string $taxRate = null, bool $finalize = true): BillingInvoice
    {
        $user = $this->superAdmin();
        $this->actingAs($user);
        $patient ??= $this->patient();
        $tax = $taxRate ? BillingTaxCategory::query()->firstOrCreate(['organization_id' => $this->organization->id, 'code' => 'VAT'.$taxRate], ['name' => 'VAT', 'rate' => $taxRate, 'is_inclusive' => false, 'is_active' => true]) : null;
        $charges = [];
        foreach ($lines as [$code, $price]) {
            $category = BillingCategory::query()->firstOrCreate(['organization_id' => $this->organization->id, 'code' => $code], ['name' => $code, 'is_active' => true, 'branch_id' => null]);
            $item = BillingItem::query()->firstOrCreate(['organization_id' => $this->organization->id, 'item_code' => 'I-'.$code], ['category_id' => $category->id, 'item_type' => 'service', 'name' => $code.' service', 'base_price' => $price, 'is_taxable' => (bool) $tax,
                'tax_category_id' => $tax?->id, 'is_clinically_chargeable' => false, 'is_active' => true, 'branch_id' => null]);
            $tax2 = $tax ? bcdiv(bcmul($price, $taxRate, 4), '100', 2) : '0.00';
            $charges[] = BillingCharge::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'billing_item_id' => $item->id, 'idempotency_key' => uniqid('chg-', true), 'status' => 'pending', 'quantity' => 1, 'currency' => 'BDT',
                'unit_price' => $price, 'gross_amount' => $price, 'discount_type' => 'none', 'discount_amount' => '0.00', 'tax_amount' => $tax2, 'net_amount' => $price, 'charged_at' => now()]);
        }
        $this->chart()->seedStarter($this->organization->id); // maps the categories just made to revenue accounts
        $invoices = app(InvoiceService::class);
        $invoice = $invoices->createDraft($patient, ['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id], $user);
        $invoices->addCharges($invoice, new Collection($charges));

        return $finalize ? app(InvoiceLifecycleService::class)->finalize($invoice, $user) : $invoice;
    }

    protected function pay(BillingInvoice $invoice, string $amount, ?BillingPaymentMethod $method = null)
    {
        return app(PaymentService::class)->collect($invoice, ['payment_method_id' => ($method ?? $this->cashMethod())->id, 'amount' => $amount], $this->superAdmin());
    }
}
