<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantContextInputTest extends TestCase
{
    use RefreshDatabase;

    private function boot(): array
    {
        $organization = Organization::factory()->create();
        $branch = Branch::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'admin']);
        $user->branches()->attach($branch->id, ['access_level' => 'manager', 'organization_id' => $organization->id]);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user->assignRole('super_admin');
        $this->actingAs($user);
        session()->put('tenant_organization_id', $organization->id);
        session()->put('tenant_branch_id', $branch->id);
        app(TenantContextResolver::class)->setOrganizationId($organization->id);
        app(TenantContextResolver::class)->setBranchId($branch->id);

        return [$organization, $branch, $user];
    }

    public function test_forms_no_longer_ask_for_organization_or_branch(): void
    {
        $this->boot();

        foreach (['/admin/patients/create', '/admin/encounters/create', '/admin/ipd/wards/create', '/admin/pharmacy/medications/create', '/admin/lab/tests/create', '/admin/billing/items/create'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('name="organization_id"', false)->assertDontSee('name="branch_id"', false);
        }
    }

    public function test_saved_records_take_organization_and_branch_from_the_login_context_not_the_client(): void
    {
        [$organization, $branch] = $this->boot();
        $other = Organization::factory()->create();
        $otherBranch = Branch::factory()->create(['organization_id' => $other->id]);

        $this->post('/admin/ipd/wards', ['code' => 'ICU', 'name' => 'ICU', 'gender_policy' => 'any', 'organization_id' => $other->id, 'branch_id' => $otherBranch->id])->assertRedirect();

        $this->assertDatabaseHas('ipd_wards', ['code' => 'ICU', 'organization_id' => $organization->id, 'branch_id' => $branch->id]);
        $this->assertDatabaseMissing('ipd_wards', ['organization_id' => $other->id]);
    }

    public function test_context_the_user_does_not_belong_to_is_rejected(): void
    {
        [, , $user] = $this->boot();
        $foreign = Organization::factory()->create();
        session()->put('tenant_organization_id', $foreign->id);

        $this->post('/admin/ipd/wards', ['code' => 'X', 'name' => 'X', 'gender_policy' => 'any'])->assertForbidden();
    }
}
