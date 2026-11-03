<?php

namespace Tests\Feature\Settings;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\Branding\BrandingService;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Dashboard\FrontDoorTestCase;

/** The hospital sets its own name, logo, favicon, contact lines and outgoing e-mail account. */
class BrandingTest extends FrontDoorTestCase
{
    private function admin(): User
    {
        return $this->actor(['settings.view', 'settings.update']);
    }

    public function test_the_page_needs_the_settings_right_and_changes_need_the_update_right(): void
    {
        $this->as($this->actor([]))->get('/settings/system')->assertForbidden();
        $this->as($this->actor(['settings.view']))->get('/settings/system')->assertOk()->assertSee('System name');
        $this->as($this->actor(['settings.view']))->put('/settings/system/general', ['app_name' => 'X'])->assertForbidden();
    }

    public function test_the_system_name_and_hospital_lines_are_saved_and_laid_over_the_config(): void
    {
        $this->as($this->admin())->put('/settings/system/general', ['app_name' => 'Premier HMS', 'hospital_name' => 'Premier Health Center', 'hospital_email' => 'info@premier.test', 'hospital_website' => 'https://premier.test'])
            ->assertRedirect('/settings/system?tab=general');

        app(BrandingService::class)->apply();

        $this->assertSame('Premier HMS', config('app.name'));
        $this->assertSame('Premier HMS', config('adminlte.title'));
        $this->assertSame('Premier Health Center', app(SettingsService::class)->get('hospital.name'));
        $this->as($this->admin())->get('/settings/system')->assertSee('Premier HMS');
    }

    public function test_a_logo_and_favicon_are_stored_replaced_and_removed(): void
    {
        Storage::fake('public');
        $me = $this->admin();

        $this->as($me)->post('/settings/system/branding', ['logo' => UploadedFile::fake()->image('hospital.png', 300, 120), 'favicon' => UploadedFile::fake()->image('fav.png', 64, 64)])->assertRedirect();
        $logo = app(SettingsService::class)->get('brand.logo');
        Storage::disk('public')->assertExists($logo);
        $this->assertStringStartsWith('branding/logo-', $logo, 'stored under a name nobody chose');

        app(BrandingService::class)->apply();
        $this->assertSame('storage/'.$logo, config('adminlte.logo_img'));
        $this->assertStringContainsString('rel="icon"', $this->as($me)->get('/settings/system?tab=branding')->getContent());

        $this->as($me)->post('/settings/system/branding', ['logo' => UploadedFile::fake()->image('new.jpg', 300, 120)]);
        Storage::disk('public')->assertMissing($logo);

        $this->as($me)->delete('/settings/system/branding/logo')->assertRedirect();
        $this->assertNull(app(BrandingService::class)->assetUrl('logo'));
    }

    public function test_only_safe_pictures_of_a_sensible_size_are_accepted(): void
    {
        Storage::fake('public');
        $me = $this->admin();

        $this->as($me)->post('/settings/system/branding', ['logo' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')])->assertSessionHasErrors('logo');
        $this->as($me)->post('/settings/system/branding', ['logo' => UploadedFile::fake()->image('tiny.png', 20, 20)])->assertSessionHasErrors('logo');
        $this->as($me)->post('/settings/system/branding', ['logo' => UploadedFile::fake()->image('huge.png', 300, 300)->size(2048)])->assertSessionHasErrors('logo');
        $this->as($me)->post('/settings/system/branding', ['favicon' => UploadedFile::fake()->create('f.exe', 10)])->assertSessionHasErrors('favicon');
    }

    public function test_the_mail_account_is_saved_with_an_encrypted_password_that_is_never_shown_or_audited(): void
    {
        $me = $this->admin();
        $this->as($me)->put('/settings/system/email', [
            'mailer' => 'smtp', 'mail_host' => 'smtp.example.test', 'mail_port' => 587, 'mail_encryption' => 'tls',
            'mail_username' => 'mailer@premier.test', 'mail_password' => 'S3cret-Pass!', 'mail_from_address' => 'no-reply@premier.test', 'mail_from_name' => 'Premier Health',
        ])->assertRedirect();

        $stored = Setting::query()->where('key', 'mail.password')->firstOrFail();
        $this->assertTrue($stored->is_sensitive);
        $this->assertStringNotContainsString('S3cret', (string) $stored->value);

        app(BrandingService::class)->apply();
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.test', config('mail.mailers.smtp.host'));
        $this->assertSame('S3cret-Pass!', config('mail.mailers.smtp.password'));
        $this->assertSame('no-reply@premier.test', config('mail.from.address'));

        $this->as($me)->get('/settings/system?tab=email')->assertDontSee('S3cret')->assertSee('A password is saved');
        $this->as($me)->get('/settings')->assertDontSee('S3cret');
        $this->assertStringNotContainsString('S3cret', json_encode(AuditLog::query()->get()->toArray()));

        // blank keeps it; the tick removes it
        $this->as($me)->put('/settings/system/email', ['mailer' => 'smtp', 'mail_host' => 'smtp.example.test', 'mail_port' => 587, 'mail_from_address' => 'no-reply@premier.test', 'mail_from_name' => 'Premier Health']);
        $this->assertTrue(app(BrandingService::class)->mailPasswordIsSet());
        $this->as($me)->put('/settings/system/email', ['mailer' => 'smtp', 'mail_host' => 'smtp.example.test', 'mail_port' => 587, 'mail_from_address' => 'no-reply@premier.test', 'mail_from_name' => 'Premier Health', 'clear_password' => 1]);
        $this->assertFalse(app(BrandingService::class)->mailPasswordIsSet());
    }

    public function test_an_smtp_account_needs_a_host_and_port(): void
    {
        $this->as($this->admin())->put('/settings/system/email', ['mailer' => 'smtp', 'mail_from_address' => 'a@b.test', 'mail_from_name' => 'X'])->assertSessionHasErrors(['mail_host', 'mail_port']);
    }

    public function test_a_test_message_is_sent_to_the_address_given(): void
    {
        Mail::fake();

        $this->as($this->admin())->post('/settings/system/email/test', ['test_to' => 'me@premier.test'])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue(AuditLog::query()->where('action', 'SETTINGS_MAIL_TEST')->exists());
    }

    public function test_footer_and_support_lines_show_in_the_footer(): void
    {
        $me = $this->admin();
        $this->as($me)->put('/settings/system/contact', ['footer_text' => 'Reg. no. 12345', 'support_email' => 'help@premier.test', 'support_phone' => '16000']);

        $this->as($me)->get('/settings/system')->assertSee('Reg. no. 12345')->assertSee('help@premier.test');
    }
}
