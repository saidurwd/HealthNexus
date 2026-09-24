<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
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
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create();
        $user->companies()->attach($company->id, ['access_level' => 'admin']);
        $user->branches()->attach($branch->id, ['access_level' => 'manager', 'company_id' => $company->id]);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user->assignRole('super_admin');
        $this->actingAs($user);
        session()->put('tenant_company_id', $company->id);
        session()->put('tenant_branch_id', $branch->id);
        app(TenantContextResolver::class)->setCompanyId($company->id);
        app(TenantContextResolver::class)->setBranchId($branch->id);

        return [$company, $branch, $user];
    }

    public function test_forms_no_longer_ask_for_company_or_branch(): void
    {
        $this->boot();

        foreach (['/admin/patients/create', '/admin/encounters/create', '/admin/ipd/wards/create', '/admin/pharmacy/medications/create', '/admin/lab/tests/create', '/admin/billing/items/create'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('name="company_id"', false)->assertDontSee('name="branch_id"', false);
        }
    }

    public function test_saved_records_take_company_and_branch_from_the_login_context_not_the_client(): void
    {
        [$company, $branch] = $this->boot();
        $other = Company::factory()->create();
        $otherBranch = Branch::factory()->create(['company_id' => $other->id]);

        $this->post('/admin/ipd/wards', ['code' => 'ICU', 'name' => 'ICU', 'gender_policy' => 'any', 'company_id' => $other->id, 'branch_id' => $otherBranch->id])->assertRedirect();

        $this->assertDatabaseHas('ipd_wards', ['code' => 'ICU', 'company_id' => $company->id, 'branch_id' => $branch->id]);
        $this->assertDatabaseMissing('ipd_wards', ['company_id' => $other->id]);
    }

    public function test_context_the_user_does_not_belong_to_is_rejected(): void
    {
        [, , $user] = $this->boot();
        $foreign = Company::factory()->create();
        session()->put('tenant_company_id', $foreign->id);

        $this->post('/admin/ipd/wards', ['code' => 'X', 'name' => 'X', 'gender_policy' => 'any'])->assertForbidden();
    }
}
