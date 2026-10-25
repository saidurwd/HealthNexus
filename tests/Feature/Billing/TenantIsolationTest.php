<?php

namespace Tests\Feature\Billing;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingPaymentMethod;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\Billing\InvoiceLifecycleService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use App\Services\TenantContextResolver;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Organization A's billing user must not be able to view or act on Organization B's invoices/payments,
 * even with full billing.* permissions, since those permissions are not tenant-scoped by
 * themselves — every policy re-checks organization/branch membership server-side.
 */
class TenantIsolationTest extends BillingTestCase
{
    private Organization $otherOrganization;

    private Branch $otherBranch;

    private BillingInvoice $otherInvoice;

    private BillingPayment $otherPayment;

    protected function setUp(): void
    {
        parent::setUp();

        // Gate::before grants super_admin every ability unconditionally, which would bypass the
        // very tenant-scope checks this test exists to verify — so this test's user gets
        // specific billing permissions instead of the BillingTestCase default super_admin role.
        $this->user->removeRole('super_admin');
        foreach (['billing.invoice.view', 'billing.invoice.finalize', 'billing.payment.view'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->otherOrganization = Organization::factory()->create();
        $this->otherBranch = Branch::factory()->create(['organization_id' => $this->otherOrganization->id]);

        $otherUser = User::factory()->create();
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $patient = Patient::factory()->create(['organization_id' => $this->otherOrganization->id]);

        $category = BillingCategory::create([
            'organization_id' => $this->otherOrganization->id, 'branch_id' => null,
            'name' => 'Consultation', 'code' => 'CONSULT', 'is_active' => true,
        ]);

        $item = BillingItem::create([
            'organization_id' => $this->otherOrganization->id, 'branch_id' => null,
            'category_id' => $category->id, 'item_code' => 'OPD-1', 'item_type' => 'consultation',
            'name' => 'OPD Consultation', 'base_price' => '500.00', 'is_taxable' => false,
            'is_clinically_chargeable' => false, 'is_active' => true,
        ]);

        $method = BillingPaymentMethod::create([
            'organization_id' => $this->otherOrganization->id, 'branch_id' => null,
            'name' => 'Cash', 'code' => 'CASH', 'type' => 'cash', 'is_active' => true,
        ]);

        $charge = BillingCharge::create([
            'organization_id' => $this->otherOrganization->id, 'branch_id' => $this->otherBranch->id,
            'patient_id' => $patient->id, 'billing_item_id' => $item->id,
            'idempotency_key' => uniqid('chg-', true), 'status' => 'pending', 'quantity' => 1,
            'currency' => 'BDT', 'unit_price' => '500.00', 'gross_amount' => '500.00',
            'discount_type' => 'none', 'discount_amount' => '0.00', 'tax_amount' => '0.00',
            'net_amount' => '500.00', 'charged_at' => now(),
        ]);

        app(TenantContextResolver::class)->setOrganizationId($this->otherOrganization->id);
        app(TenantContextResolver::class)->setBranchId($this->otherBranch->id);

        $invoices = app(InvoiceService::class);
        $lifecycle = app(InvoiceLifecycleService::class);
        $payments = app(PaymentService::class);

        $this->otherInvoice = $invoices->createDraft($patient, [
            'organization_id' => $this->otherOrganization->id, 'branch_id' => $this->otherBranch->id,
        ], $otherUser);
        $invoices->addCharges($this->otherInvoice, new Collection([$charge]));
        $this->otherInvoice = $lifecycle->finalize($this->otherInvoice, $otherUser);

        $this->otherPayment = $payments->collect($this->otherInvoice, [
            'payment_method_id' => $method->id, 'amount' => '500.00',
        ], $otherUser);

        // Restore this test's own tenant context (BillingTestCase::setUp already set it, but
        // creating the other organization's fixtures above overwrote the resolver singleton).
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
        app(TenantContextResolver::class)->setBranchId($this->branch->id);
        session()->put('tenant_organization_id', $this->organization->id);
        session()->put('tenant_branch_id', $this->branch->id);
    }

    public function test_cannot_view_another_organizations_invoice(): void
    {
        $response = $this->get('/admin/billing/invoices/'.$this->otherInvoice->id);

        $response->assertForbidden();
    }

    public function test_cannot_view_another_organizations_payment(): void
    {
        $response = $this->get('/admin/billing/payments/'.$this->otherPayment->id);

        $response->assertForbidden();
    }

    public function test_invoice_index_only_lists_own_organizations_invoices(): void
    {
        $response = $this->get('/admin/billing/invoices');

        $response->assertOk();
        $response->assertViewHas('invoices', function ($invoices) {
            return ! $invoices->pluck('id')->contains($this->otherInvoice->id);
        });
    }

    public function test_cannot_finalize_another_organizations_invoice(): void
    {
        $response = $this->post('/admin/billing/invoices/'.$this->otherInvoice->id.'/finalize');

        $response->assertForbidden();
    }

    public function test_forTenant_scope_excludes_other_organizations_rows_at_the_query_level(): void
    {
        $ids = BillingInvoice::query()
            ->forTenant($this->organization->id)
            ->pluck('id');

        $this->assertFalse($ids->contains($this->otherInvoice->id));
    }
}
