<?php

namespace Tests\Feature\Localization;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
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
        $this->assertSame('Save', __('core.save'));

        App::setLocale('bn');
        $this->assertSame('সংরক্ষণ করুন', __('core.save'));
    }

    public function test_missing_key_in_partial_locale_falls_back_to_default_locale(): void
    {
        App::setLocale('ar');

        // 'update' is intentionally not translated in lang/ar/core.php yet.
        $this->assertSame('Update', __('core.update'));
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
        $company = \App\Models\Company::factory()->create();
        $user = User::factory()->create(['locale' => 'bn']);
        $user->companies()->attach($company->id, ['access_level' => 'admin']);

        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage companies', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'patients.view', 'guard_name' => 'web']);
        $user->givePermissionTo('manage companies');
        $user->givePermissionTo('patients.view');

        $this->actingAs($user);
        session()->put('tenant_company_id', $company->id);
        app(\App\Services\TenantContextResolver::class)->setCompanyId($company->id);

        $response = $this->get('/admin/patients');

        $response->assertOk();
        $response->assertSee('রোগীরা'); // "Patients" in Bangla
    }
}
