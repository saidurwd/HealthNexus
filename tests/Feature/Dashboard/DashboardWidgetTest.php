<?php

namespace Tests\Feature\Dashboard;

use App\Models\Branch;
use App\Models\Organization;
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

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->organization = Organization::factory()->create();
        $branch = Branch::factory()->create(['organization_id' => $this->organization->id]);

        $this->user = User::factory()->create();
        $this->user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);

        foreach (['manage organizations', 'manage users', 'manage branches'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->actingAs($this->user);
        // /dashboard is wrapped in EnsureTenantContext, which requires both an organization AND a
        // branch to be resolved — unlike the admin.* routes, which don't enforce this.
        session()->put('tenant_organization_id', $this->organization->id);
        session()->put('tenant_branch_id', $branch->id);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
        app(TenantContextResolver::class)->setBranchId($branch->id);
    }

    public function test_registry_hides_widgets_the_user_lacks_permission_for(): void
    {
        $registry = new DashboardWidgetRegistry;
        $registry->register(new DashboardWidget(
            key: 'gated',
            title: 'Gated',
            valueResolver: fn () => 'should-not-be-called',
            permission: 'manage organizations',
        ));

        $this->assertCount(0, $registry->forUser($this->user));

        $this->user->givePermissionTo('manage organizations');
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
            permission: 'manage organizations',
        ));

        $registry->forUser($this->user);

        $this->assertFalse($called);
    }

    public function test_dashboard_page_renders_core_widgets(): void
    {
        $this->user->givePermissionTo(['manage organizations', 'manage users', 'manage branches']);

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('widgets');

        $keys = collect($response->viewData('widgets'))->pluck('key');
        $this->assertContains('total_users', $keys);
        $this->assertContains('total_organizations', $keys);
        $this->assertContains('total_branches', $keys);
    }

    public function test_dashboard_widget_count_reflects_real_data(): void
    {
        $this->user->givePermissionTo('manage organizations');

        $response = $this->get('/dashboard');

        $widget = collect($response->viewData('widgets'))->firstWhere('key', 'total_organizations');
        $this->assertSame(Organization::count(), $widget->resolveValue());
    }
}
