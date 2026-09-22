<?php

namespace Tests\Feature\Phase0;

use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\SettingsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsTableSeeder::class);

        Permission::create(['name' => 'settings.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'settings.update', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['password' => 'password']);
        $this->admin->givePermissionTo(['settings.view', 'settings.update']);

        $this->viewer = User::factory()->create();
        $this->viewer->givePermissionTo(['settings.view']);
    }

    public function test_guest_is_redirected_from_settings(): void
    {
        $this->get('/admin/settings')->assertRedirect('/login');
    }

    public function test_viewer_can_see_settings(): void
    {
        $this->actingAs($this->viewer)
            ->get('/admin/settings')
            ->assertViewIs('admin.settings.index')
            ->assertViewHas('settings');
    }

    public function test_user_without_permission_cannot_update_settings(): void
    {
        $this->actingAs($this->viewer)
            ->put('/admin/settings', ['settings' => ['hospital.name' => 'Hacked']])
            ->assertForbidden();
    }

    public function test_admin_can_update_setting_and_service_reflects_change(): void
    {
        $service = app(SettingsService::class);

        $this->assertSame('HealthNexus', $service->get('hospital.name'));

        $old = $service->get('hospital.name');

        $this->actingAs($this->admin)
            ->put('/admin/settings', ['settings' => ['hospital.name' => 'New Hospital Name']])
            ->assertRedirect('/admin/settings');

        $this->assertDatabaseHas('settings', [
            'key' => 'hospital.name',
            'value' => 'New Hospital Name',
        ]);

        $this->assertSame('New Hospital Name', $service->get('hospital.name', $old));
    }

    public function test_sensitive_settings_are_excluded_from_api(): void
    {
        $sensitive = Setting::where('key', 'security.password_min_length')->first();
        $this->assertTrue($sensitive->is_sensitive);

        Sanctum::actingAs($this->viewer);

        $response = $this->get('/api/v1/settings')->assertOk();

        $body = $response->decodeResponseJson();

        $this->assertArrayNotHasKey('security.password_min_length', $body['data']['security'] ?? $body['data']);
        $this->assertSame(120, $body['data']['security']['session_timeout']);
        $this->assertSame('HealthNexus', $body['data']['hospital']['name']);
    }

    public function test_default_settings_are_seeded(): void
    {
        $service = app(SettingsService::class);

        $this->assertSame('en', $service->get('system.locale'));
        $this->assertSame('UTC', $service->get('system.timezone'));
        $this->assertSame('USD', $service->get('system.currency'));
        $this->assertFalse($service->has('nonexistent.setting'));
    }

    public function test_settings_are_cached_and_flushed(): void
    {
        $service = app(SettingsService::class);

        $this->assertSame('HealthNexus', $service->get('hospital.name'));

        $service->set('hospital.name', 'First Update');
        $this->assertSame('First Update', $service->get('hospital.name'));

        $service->set('hospital.name', 'Second Update');
        $this->assertSame('Second Update', $service->get('hospital.name'));
    }

    public function test_locked_setting_cannot_be_overwritten(): void
    {
        $service = app(SettingsService::class);

        $service->set('hospital.name', 'Overwritten');
        $service->flush();

        $locked = Setting::where('key', 'hospital.name')->first();
        $locked->update(['is_locked' => true]);
        $service->flush();

        $service->set('hospital.name', 'Should Not Persist');
        $service->flush();

        $this->assertDatabaseHas('settings', ['key' => 'hospital.name', 'value' => 'Overwritten']);
    }
}
