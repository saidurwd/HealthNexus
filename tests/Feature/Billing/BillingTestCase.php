<?php

namespace Tests\Feature\Billing;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class BillingTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie caches permission/role lookups across requests; RefreshDatabase wipes the
        // tables between tests but not that cache, so stale IDs from a prior test in the same
        // process can make hasRole()/can() checks silently fail. Clear it every test.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);

        // super_admin bypasses all permission checks via Gate::before — keeps business-logic
        // tests focused on the behavior under test rather than re-declaring every permission.
        // Permission-specific behavior is covered separately (see TenantIsolationTest).
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->user->assignRole('super_admin');

        $this->actingAs($this->user);

        session()->put('tenant_company_id', $this->company->id);
        session()->put('tenant_branch_id', $this->branch->id);
        app(TenantContextResolver::class)->setCompanyId($this->company->id);
        app(TenantContextResolver::class)->setBranchId($this->branch->id);
    }
}
