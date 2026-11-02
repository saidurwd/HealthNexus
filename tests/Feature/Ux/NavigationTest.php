<?php

namespace Tests\Feature\Ux;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use App\Services\ModuleManager;
use App\Services\TenantContextResolver;
use App\Support\ModuleMenuFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** The left navigation: sections in the order of a hospital's working day, two levels, every link real, and nothing shown that the person cannot use or the hospital has switched off. */
class NavigationTest extends TestCase
{
    use RefreshDatabase;

    private const SECTIONS = ['Front desk', 'Clinical care', 'Diagnostics and pharmacy', 'Billing and insurance', 'Supply chain', 'People and payroll', 'Finance', 'Hospital services', 'Insights and connections', 'Administration', 'Help'];

    /** @return list<array<string,mixed>> */
    private function menu(): array
    {
        return array_values(array_filter(config('adminlte.menu'), fn ($i) => ! isset($i['type']) && (isset($i['text']) || isset($i['header']))));
    }

    public function test_sections_come_in_the_order_of_the_working_day_and_every_module_sits_under_one(): void
    {
        $headers = collect($this->menu())->pluck('header')->filter()->values()->all();
        $this->assertSame(self::SECTIONS, $headers);
        $this->assertSame('Dashboard', $this->menu()[0]['text'], 'the dashboard is first');
        $this->assertSame('Front desk', collect($this->menu())->first(fn ($i) => isset($i['header']))['header'], 'reception is the first section, straight after the dashboards and the personal page');
        $this->assertSame('Help and support', $this->menu()[array_key_last($this->menu())]['text'], 'help is last');
        $registry = array_keys(array_filter(config('modules.modules') ?? config('modules'), fn ($m) => is_array($m) && ($m['built'] ?? false)));
        $inMenu = collect($this->menu())->pluck('module')->filter()->unique();
        foreach ($inMenu as $module) {
            $this->assertContains($module, $registry, "menu module [$module] is a built module");
        }
    }

    public function test_every_menu_link_is_a_real_page_and_nothing_points_nowhere(): void
    {
        $paths = collect(Route::getRoutes()->getRoutes())->filter(fn ($r) => in_array('GET', $r->methods(), true))->map(fn ($r) => '/'.ltrim($r->uri(), '/'))->all();
        $bad = [];
        $walk = function (array $items) use (&$walk, &$bad, $paths) {
            foreach ($items as $i) {
                $url = $i['url'] ?? null;
                if (isset($i['submenu'])) {
                    $walk($i['submenu']);
                } elseif ($url === '#' || $url === null) {
                    $bad[] = ($i['text'] ?? '?').' has no page';
                } elseif (! in_array(parse_url($url, PHP_URL_PATH), $paths, true)) {
                    $bad[] = ($i['text'] ?? '?').' → '.$url;
                }
            }
        };
        $walk(array_filter($this->menu(), fn ($i) => isset($i['text'])));

        $this->assertSame([], $bad);
    }

    public function test_a_headed_section_lists_its_permissions_or_modules_so_it_can_hide_itself(): void
    {
        $without = collect($this->menu())->filter(fn ($i) => isset($i['header']) && $i['header'] !== 'Help' && ! isset($i['can']) && ! isset($i['modules']))->pluck('header')->all();
        $this->assertSame([], $without);
    }

    public function test_the_module_filter_hides_a_header_only_when_all_its_modules_are_off(): void
    {
        $filter = new ModuleMenuFilter;
        $modules = $this->createMock(ModuleManager::class);
        $modules->method('isEnabled')->willReturnCallback(fn ($key) => $key === 'lab');
        $this->app->instance(ModuleManager::class, $modules);

        $this->assertEmpty($filter->transform(['header' => 'Diagnostics', 'modules' => ['lab', 'radiology']])['restricted'] ?? null);
        $this->assertTrue($filter->transform(['header' => 'Supply chain', 'modules' => ['inventory']])['restricted']);
        $this->assertTrue($filter->transform(['text' => 'Stores', 'module' => 'inventory'])['restricted']);
        $this->assertEmpty($filter->transform(['text' => 'Patients'])['restricted'] ?? null);
    }

    public function test_no_module_has_more_than_two_levels_except_those_listed(): void
    {
        $three = [];
        foreach ($this->menu() as $top) {
            foreach ($top['submenu'] ?? [] as $second) {
                if (isset($second['submenu'])) {
                    $three[] = $top['text'];
                    break;
                }
            }
        }
        // These modules still group their pages one level deeper. Flattening them is a separate slice (docs/ux/navigation.md); new modules must stay at two levels.
        $this->assertEqualsCanonicalizing(['Billing and cashier', 'Laboratory (Pathology)', 'Radiology and imaging', 'Pharmacy', 'IPD (Indoor)', 'Nursing', 'OT / Surgery', 'ICU / Critical Care'], $three);
    }

    public function test_hospital_words_for_bangladesh_are_used_and_translated(): void
    {
        $texts = collect($this->menu())->flatMap(fn ($i) => array_filter([$i['text'] ?? $i['header'] ?? null]))->all();
        foreach (['OPD (Outdoor)', 'IPD (Indoor)', 'Laboratory (Pathology)', 'Billing and cashier', 'Help and support'] as $label) {
            $this->assertContains($label, $texts, $label);
        }
        $bn = json_decode(file_get_contents(base_path('lang/bn.json')), true);
        foreach (array_merge($texts, self::SECTIONS) as $label) {
            $this->assertNotEmpty($bn[$label] ?? null, "Bangla is missing for [$label]");
        }
    }

    public function test_a_front_desk_user_sees_the_front_desk_and_help_and_nothing_they_cannot_use(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $org = Organization::factory()->create();
        $branch = Branch::factory()->create(['organization_id' => $org->id]);
        $user = User::factory()->create();
        $user->organizations()->attach($org->id, ['access_level' => 'staff']);
        $user->branches()->attach($branch->id, ['access_level' => 'staff', 'organization_id' => $org->id]);
        foreach (['patients.view', 'appointments.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $role = Role::firstOrCreate(['name' => 'nav_receptionist', 'guard_name' => 'web']);
        $role->givePermissionTo(['patients.view', 'appointments.view']);
        $user->assignRole($role);
        $this->actingAs($user);
        session()->put(['tenant_organization_id' => $org->id, 'tenant_branch_id' => $branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($org->id);
        app(TenantContextResolver::class)->setBranchId($branch->id);

        $html = $this->get('/dashboard')->assertOk()->getContent();

        foreach (['Front desk', 'All Patients', 'Help and support', 'Quick-start cards'] as $shown) {
            $this->assertStringContainsString($shown, $html, $shown);
        }
        foreach (['Chart of accounts', 'Clinical care', 'Supply chain', 'Insights and connections', 'Security and audit', 'Permissions'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $html, $hidden);
        }
    }
}
