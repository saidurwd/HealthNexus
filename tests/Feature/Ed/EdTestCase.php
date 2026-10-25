<?php

namespace Tests\Feature\Ed;

use App\Models\Branch;
use App\Models\Ed\EdBay;
use App\Models\Ed\EdZone;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class EdTestCase extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Branch $branch;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->user = $this->staff(['super_admin']);
        $this->actingAs($this->user);
        $this->context();
    }

    protected function context(?Organization $organization = null, ?Branch $branch = null): void
    {
        $organization ??= $this->organization;
        $branch ??= $this->branch;
        session()->put(['tenant_organization_id' => $organization->id, 'tenant_branch_id' => $branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($organization->id);
        app(TenantContextResolver::class)->setBranchId($branch->id);
    }

    protected function staff(array $roles = [], ?Organization $organization = null, ?Branch $branch = null): User
    {
        $organization ??= $this->organization;
        $branch ??= $this->branch;
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'admin']);
        $user->branches()->attach($branch->id, ['access_level' => 'manager', 'organization_id' => $organization->id]);
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $user->assignRole($role);
        }

        return $user;
    }

    protected function bay(string $code = 'A1', string $zoneCode = 'ACUTE'): EdBay
    {
        $zone = EdZone::firstOrCreate(
            ['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'code' => $zoneCode],
            ['name' => "Zone $zoneCode", 'zone_type' => 'acute', 'is_active' => true],
        );

        return EdBay::create(['organization_id' => $this->organization->id, 'zone_id' => $zone->id, 'code' => $code, 'status' => 'available', 'is_active' => true]);
    }

    protected function patient(array $o = []): Patient
    {
        return Patient::factory()->create(array_merge(['organization_id' => $this->organization->id, 'middle_name' => null], $o));
    }
}
