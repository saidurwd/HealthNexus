<?php

namespace Tests\Feature\Billing;

use App\Models\Branch;
use App\Models\Organization;
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

    protected Organization $organization;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie caches permission/role lookups across requests; RefreshDatabase wipes the
        // tables between tests but not that cache, so stale IDs from a prior test in the same
        // process can make hasRole()/can() checks silently fail. Clear it every test.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);

        $this->user = User::factory()->create();
        $this->user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);

        // super_admin bypasses all permission checks via Gate::before — keeps business-logic
        // tests focused on the behavior under test rather than re-declaring every permission.
        // Permission-specific behavior is covered separately (see TenantIsolationTest).
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->user->assignRole('super_admin');

        $this->actingAs($this->user);

        session()->put('tenant_organization_id', $this->organization->id);
        session()->put('tenant_branch_id', $this->branch->id);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
        app(TenantContextResolver::class)->setBranchId($this->branch->id);
    }
}
