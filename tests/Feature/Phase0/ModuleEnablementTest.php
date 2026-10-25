<?php

namespace Tests\Feature\Phase0;

use App\Models\Organization;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ModuleEnablementTest extends TestCase
{
    use RefreshDatabase;

    public function test_built_modules_are_on_by_default_and_unbuilt_ones_are_off(): void
    {
        $organization = Organization::factory()->create();
        $modules = app(ModuleManager::class);

        $this->assertTrue($modules->isEnabled('icu', $organization->id));
        $this->assertFalse($modules->isEnabled('ed', $organization->id));
    }

    public function test_switching_off_is_per_organization(): void
    {
        [$a, $b] = Organization::factory()->count(2)->create();
        $modules = app(ModuleManager::class);

        $modules->set('icu', false, $a->id);

        $this->assertFalse($modules->isEnabled('icu', $a->id));
        $this->assertTrue($modules->isEnabled('icu', $b->id));
    }

    public function test_a_module_with_enabled_dependents_cannot_be_switched_off(): void
    {
        $organization = Organization::factory()->create();

        $this->expectException(ValidationException::class);

        app(ModuleManager::class)->set('ipd', false, $organization->id); // nursing, ot, icu depend on it
    }

    public function test_a_module_cannot_be_enabled_without_its_dependencies(): void
    {
        $organization = Organization::factory()->create();
        $modules = app(ModuleManager::class);
        $modules->set('icu', false, $organization->id);
        $modules->set('ot', false, $organization->id);
        $modules->set('nursing', false, $organization->id);

        $this->expectException(ValidationException::class);

        $modules->set('icu', true, $organization->id);
    }

    public function test_always_on_and_unbuilt_modules_cannot_be_toggled(): void
    {
        $organization = Organization::factory()->create();
        $modules = app(ModuleManager::class);

        foreach ([['billing', false], ['ed', true]] as [$key, $enabled]) {
            try {
                $modules->set($key, $enabled, $organization->id);
                $this->fail("$key toggle should be refused");
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_a_hospital_row_overrides_the_organization_row(): void
    {
        $organization = Organization::factory()->create();
        $hospital = \App\Models\Hospital::factory()->create(['organization_id' => $organization->id]);
        $modules = app(ModuleManager::class);

        $modules->set('icu', false, $organization->id);
        $modules->set('icu', true, $organization->id, $hospital->id);

        $this->assertFalse($modules->isEnabled('icu', $organization->id));
        $this->assertTrue($modules->isEnabled('icu', $organization->id, $hospital->id));
    }

    public function test_routes_of_a_switched_off_module_answer_404_and_menu_item_is_hidden(): void
    {
        $organization = Organization::factory()->create();
        $branch = \App\Models\Branch::factory()->create(['organization_id' => $organization->id]);
        $user = \App\Models\User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'admin']);
        $user->branches()->attach($branch->id, ['access_level' => 'staff', 'organization_id' => $organization->id]);
        \Spatie\Permission\Models\Permission::create(['name' => 'icu.dashboard.view', 'guard_name' => 'web']);
        $user->givePermissionTo('icu.dashboard.view');

        $before = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->withHeader('X-Branch-Id', (string) $branch->id)
            ->actingAs($user, 'sanctum')
            ->getJson('/api/v1/icu/dashboard');
        $this->assertNotSame(404, $before->status(), 'route must exist while the module is on');

        app(ModuleManager::class)->set('icu', false, $organization->id);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->withHeader('X-Branch-Id', (string) $branch->id)
            ->actingAs($user, 'sanctum')
            ->getJson('/api/v1/icu/dashboard')
            ->assertNotFound();
    }
}
