<?php

namespace Tests\Feature\Patients;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regression guard for the exact route-ordering bug already hit (and fixed) three times in the
 * Billing module this session: a wildcard {patient} show route registered before a literal
 * "create"/"search"/etc. path swallows the literal segment as the route-model-binding key and
 * 404s instead of rendering the intended page.
 */
class PatientRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company->id, ['access_level' => 'admin']);

        foreach (['patients.view', 'patients.create', 'patients.merge', 'patients.amend.approve'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        $this->actingAs($user);
    }

    public function test_create_page_is_not_swallowed_by_the_show_wildcard(): void
    {
        $this->get('/admin/patients/create')->assertOk();
    }

    public function test_search_page_is_not_swallowed_by_the_show_wildcard(): void
    {
        $this->get('/admin/patients/search')->assertOk();
    }

    public function test_duplicates_page_is_not_swallowed_by_the_show_wildcard(): void
    {
        $this->get('/admin/patients/duplicates')->assertOk();
    }

    public function test_amendments_page_is_not_swallowed_by_the_show_wildcard(): void
    {
        $this->get('/admin/patients/amendments')->assertOk();
    }
}
