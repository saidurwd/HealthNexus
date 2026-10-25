<?php

namespace Tests\Feature\Localization;

use App\Models\Notification;
use App\Models\Organization;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/** H-10: a Bangla-only user sees Bangla screens, validation errors and notifications. */
class BanglaScreensTest extends TestCase
{
    use RefreshDatabase;

    private function bengaliUser(): User
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['locale' => 'bn']);
        $user->organizations()->attach($organization->id, ['access_level' => 'admin']);
        Permission::firstOrCreate(['name' => 'patients.view', 'guard_name' => 'web']);
        $user->givePermissionTo('patients.view');
        app(SettingsService::class)->set('localization.supported_locales', ['en' => 'English', 'bn' => 'Bangla']);

        $this->actingAs($user);
        session()->put('tenant_organization_id', $organization->id);

        return $user;
    }

    public function test_patient_list_renders_in_bangla_for_a_bangla_user(): void
    {
        $this->bengaliUser();

        $this->get('/admin/patients')->assertOk()->assertSee('রোগী')->assertSee('খুঁজুন');
    }

    public function test_the_same_page_stays_english_for_an_english_user(): void
    {
        $user = $this->bengaliUser();
        $user->update(['locale' => 'en']);

        $this->get('/admin/patients')->assertOk()->assertSee('Search')->assertDontSee('খুঁজুন');
    }

    public function test_validation_messages_are_in_bangla(): void
    {
        app()->setLocale('bn');

        $errors = Validator::make(['email' => 'nope'], ['email' => 'required|email', 'name' => 'required'])->errors();

        $this->assertStringContainsString('সঠিক ইমেইল ঠিকানা', $errors->first('email'));
        $this->assertStringContainsString('আবশ্যক', $errors->first('name'));
    }

    public function test_notifications_are_written_in_the_recipients_language(): void
    {
        $bangla = User::factory()->create(['locale' => 'bn']);
        $english = User::factory()->create(['locale' => 'en']);

        app(NotificationService::class)->send([$bangla, $english], 'critical_result', ['test' => 'Potassium', 'patient' => 'Rahim', 'value' => '6.8']);

        $this->assertDatabaseHas('notifications', ['user_id' => $bangla->id, 'title' => 'জরুরি ল্যাব ফলাফল']);
        $this->assertDatabaseHas('notifications', ['user_id' => $english->id, 'title' => 'Critical Lab Result']);
        $this->assertStringContainsString('Rahim এর Potassium জরুরি: 6.8', Notification::where('user_id', $bangla->id)->value('message'));
    }
}
