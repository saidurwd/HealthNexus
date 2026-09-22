<?php

namespace Tests\Feature\Dashboard;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\Dashboard\DashboardWidget;
use App\Services\Dashboard\DashboardWidgetRegistry;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($branch->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);

        foreach (['manage companies', 'manage users', 'manage branches'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->actingAs($this->user);
        // /dashboard is wrapped in EnsureTenantContext, which requires both a company AND a
        // branch to be resolved — unlike the admin.* routes, which don't enforce this.
        session()->put('tenant_company_id', $this->company->id);
        session()->put('tenant_branch_id', $branch->id);
        app(TenantContextResolver::class)->setCompanyId($this->company->id);
        app(TenantContextResolver::class)->setBranchId($branch->id);
    }

    public function test_registry_hides_widgets_the_user_lacks_permission_for(): void
    {
        $registry = new DashboardWidgetRegistry;
        $registry->register(new DashboardWidget(
            key: 'gated',
            title: 'Gated',
            valueResolver: fn () => 'should-not-be-called',
            permission: 'manage companies',
        ));

        $this->assertCount(0, $registry->forUser($this->user));

        $this->user->givePermissionTo('manage companies');
        $this->assertCount(1, $registry->forUser($this->user));
    }

    public function test_widget_with_no_permission_is_visible_to_everyone(): void
    {
        $registry = new DashboardWidgetRegistry;
        $registry->register(new DashboardWidget(key: 'open', title: 'Open', valueResolver: fn () => 1));

        $this->assertCount(1, $registry->forUser($this->user));
    }

    public function test_gated_widgets_value_resolver_is_never_called_when_hidden(): void
    {
        $called = false;

        $registry = new DashboardWidgetRegistry;
        $registry->register(new DashboardWidget(
            key: 'expensive',
            title: 'Expensive',
            valueResolver: function () use (&$called) {
                $called = true;

                return 42;
            },
            permission: 'manage companies',
        ));

        $registry->forUser($this->user);

        $this->assertFalse($called);
    }

    public function test_dashboard_page_renders_core_widgets(): void
    {
        $this->user->givePermissionTo(['manage companies', 'manage users', 'manage branches']);

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('widgets');

        $keys = collect($response->viewData('widgets'))->pluck('key');
        $this->assertContains('total_users', $keys);
        $this->assertContains('total_companies', $keys);
        $this->assertContains('total_branches', $keys);
    }

    public function test_dashboard_widget_count_reflects_real_data(): void
    {
        $this->user->givePermissionTo('manage companies');

        $response = $this->get('/dashboard');

        $widget = collect($response->viewData('widgets'))->firstWhere('key', 'total_companies');
        $this->assertSame(Company::count(), $widget->resolveValue());
    }
}
