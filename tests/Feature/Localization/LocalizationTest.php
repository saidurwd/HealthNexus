<?php

namespace Tests\Feature\Localization;

use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create([
            'group' => 'localization',
            'key' => 'localization.supported_locales',
            'value' => json_encode(['en' => 'English', 'bn' => 'Bangla', 'ar' => 'Arabic']),
            'type' => 'json',
        ]);
    }

    public function test_lang_files_load_and_resolve_keys(): void
    {
        App::setLocale('en');
        $this->assertSame('Save', __('ui_core.save'));

        App::setLocale('bn');
        $this->assertSame('সংরক্ষণ করুন', __('ui_core.save'));
    }

    public function test_missing_key_in_partial_locale_falls_back_to_default_locale(): void
    {
        App::setLocale('ar');

        // 'update' is intentionally not translated in lang/ar/ui_core.php yet.
        $this->assertSame('Update', __('ui_core.update'));
    }

    public function test_middleware_defaults_to_app_locale_when_nothing_else_is_set(): void
    {
        $this->get('/login');

        $this->assertSame(config('app.locale'), App::getLocale());
    }

    public function test_middleware_uses_authenticated_users_saved_locale(): void
    {
        $user = User::factory()->create(['locale' => 'bn']);

        $this->actingAs($user)->get('/login');

        $this->assertSame('bn', App::getLocale());
    }

    public function test_middleware_rejects_an_unsupported_locale_and_falls_back(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->actingAs($user)->get('/login');

        $this->assertSame(config('app.locale'), App::getLocale());
    }

    public function test_locale_switch_endpoint_persists_to_session_and_user(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        $this->actingAs($user);

        $this->post('/locale/bn')->assertRedirect();

        $this->assertSame('bn', session('locale'));
        $this->assertSame('bn', $user->fresh()->locale);
    }

    public function test_locale_switch_rejects_unsupported_locale(): void
    {
        $this->post('/locale/xx')->assertNotFound();
    }

    public function test_patients_index_renders_translated_strings_in_bangla(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['locale' => 'bn']);
        $user->organizations()->attach($organization->id, ['access_level' => 'admin']);

        Permission::firstOrCreate(['name' => 'manage organizations', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'patients.view', 'guard_name' => 'web']);
        $user->givePermissionTo('manage organizations');
        $user->givePermissionTo('patients.view');

        $this->actingAs($user);
        session()->put('tenant_organization_id', $organization->id);
        app(TenantContextResolver::class)->setOrganizationId($organization->id);

        $response = $this->get('/admin/patients');

        $response->assertOk();
        $response->assertSee('রোগীরা'); // "Patients" in Bangla
    }
}
