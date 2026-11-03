<?php

namespace App\Services\Branding;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * What the hospital calls the system and how it looks and mails: name, logo, favicon, footer and contact lines, and the outgoing e-mail account.
 * Stored as settings (groups `brand`, `mail`, `hospital`) and laid over the config at start-up, so every screen, e-mail and print uses it without knowing where it came from.
 * The mail password is kept encrypted, is never shown again and is never written to the audit trail.
 */
class BrandingService
{
    public const ASSETS = ['logo' => 'brand.logo', 'favicon' => 'brand.favicon'];

    private const DISK = 'public';

    public function __construct(private readonly SettingsService $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        // An error page must still draw when the database cannot be read, so a failed lookup is just "not set".
        try {
            $value = $this->settings->get($key);
        } catch (\Throwable) {
            return $default;
        }

        return $value === null || $value === '' ? $default : $value;
    }

    /** The values the set-up page shows, with the shipped defaults where nothing has been saved. */
    public function values(): array
    {
        return [
            'app_name' => (string) $this->get('brand.app_name', config('app.name')),
            'hospital_name' => (string) $this->get('hospital.name', ''),
            'hospital_phone' => (string) $this->get('hospital.phone', ''),
            'hospital_email' => (string) $this->get('hospital.email', ''),
            'hospital_address' => (string) $this->get('hospital.address', ''),
            'hospital_website' => (string) $this->get('hospital.website', ''),
            'footer_text' => (string) $this->get('brand.footer_text', ''),
            'support_email' => (string) $this->get('brand.support_email', ''),
            'support_phone' => (string) $this->get('brand.support_phone', ''),
            'support_url' => (string) $this->get('brand.support_url', ''),
            'mailer' => (string) $this->get('mail.mailer', 'log'),
            'mail_host' => (string) $this->get('mail.host', ''),
            'mail_port' => (string) $this->get('mail.port', '587'),
            'mail_encryption' => (string) $this->get('mail.encryption', 'tls'),
            'mail_username' => (string) $this->get('mail.username', ''),
            'mail_from_address' => (string) $this->get('mail.from_address', config('mail.from.address')),
            'mail_from_name' => (string) $this->get('mail.from_name', config('mail.from.name')),
        ];
    }

    public function mailPasswordIsSet(): bool
    {
        return (string) $this->rawPassword() !== '';
    }

    public function assetUrl(string $kind): ?string
    {
        $path = $this->get(self::ASSETS[$kind] ?? '');

        try {
            return is_string($path) && Storage::disk(self::DISK)->exists($path) ? Storage::disk(self::DISK)->url($path) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @param  array<string, mixed>  $data */
    public function saveGeneral(array $data): void
    {
        $this->settings->set('brand.app_name', $data['app_name'], 'brand');
        $this->settings->set('system.app_name', $data['app_name'], 'system');
        foreach (['name', 'phone', 'email', 'address', 'website'] as $field) {
            $this->settings->set('hospital.'.$field, $data['hospital_'.$field] ?? null, 'hospital');
        }
    }

    /** @param  array<string, mixed>  $data */
    public function saveFooter(array $data): void
    {
        foreach (['footer_text', 'support_email', 'support_phone', 'support_url'] as $field) {
            $this->settings->set('brand.'.$field, $data[$field] ?? null, 'brand');
        }
    }

    /** Stores an uploaded picture under a name nobody chose, and forgets the one it replaces. */
    public function storeAsset(string $kind, UploadedFile $file): void
    {
        $key = self::ASSETS[$kind];
        $old = $this->get($key);
        $path = $file->storeAs('branding', $kind.'-'.Str::random(12).'.'.($file->guessExtension() ?: 'png'), self::DISK);
        $this->settings->set($key, $path, 'brand');
        if (is_string($old)) {
            Storage::disk(self::DISK)->delete($old);
        }
    }

    public function removeAsset(string $kind): void
    {
        $key = self::ASSETS[$kind];
        $old = $this->get($key);
        if (is_string($old)) {
            Storage::disk(self::DISK)->delete($old);
        }
        $this->settings->set($key, null, 'brand');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed> what changed, without the password
     */
    public function saveMail(array $data): array
    {
        $this->settings->set('mail.mailer', $data['mailer'], 'mail');
        $this->settings->set('mail.host', $data['mail_host'] ?? null, 'mail');
        $this->settings->set('mail.port', $data['mail_port'] ?? null, 'mail');
        $this->settings->set('mail.encryption', $data['mail_encryption'] ?? 'tls', 'mail');
        $this->settings->set('mail.username', $data['mail_username'] ?? null, 'mail');
        $this->settings->set('mail.from_address', $data['mail_from_address'], 'mail');
        $this->settings->set('mail.from_name', $data['mail_from_name'], 'mail');

        $changed = collect($data)->except(['mail_password', 'clear_password'])->all();
        if (! empty($data['clear_password'])) {
            $this->storePassword(null);
            $changed['mail_password'] = 'removed';
        } elseif (($data['mail_password'] ?? '') !== '') {
            $this->storePassword((string) $data['mail_password']);
            $changed['mail_password'] = 'changed';
        }

        return $changed;
    }

    /** Lays the saved name, logo, favicon and mail account over the config. Quiet when the settings table is not there yet (first install, migrations). */
    public function apply(): void
    {
        try {
            $name = $this->get('brand.app_name');
            if (is_string($name)) {
                config([
                    'app.name' => $name,
                    'adminlte.title' => $name,
                    'adminlte.title_postfix' => '- '.$name,
                    'adminlte.logo' => '<b>'.e($name).'</b>',
                    'adminlte.logo_img_alt' => $name.' logo',
                ]);
            }

            $logo = $this->get('brand.logo');
            if (is_string($logo) && Storage::disk(self::DISK)->exists($logo)) {
                config([
                    // The hospital's own logo stands alone: no system name beside it.
                    'adminlte.logo' => '',
                    'adminlte.logo_img_class' => 'brand-image brand-logo-only',
                    'adminlte.logo_img' => 'storage/'.$logo,
                    'adminlte.auth_logo.enabled' => true,
                    'adminlte.auth_logo.img.path' => 'storage/'.$logo,
                    'adminlte.auth_logo.img.alt' => ($name ?? config('app.name')).' logo',
                    'adminlte.auth_logo.img.width' => 'auto',
                    'adminlte.auth_logo.img.height' => 60,
                ]);
            }

            $this->applyMail();
        } catch (\Throwable) {
            // no settings yet
        }
    }

    private function applyMail(): void
    {
        $from = $this->get('mail.from_address');
        if (is_string($from)) {
            config(['mail.from.address' => $from, 'mail.from.name' => (string) $this->get('mail.from_name', config('mail.from.name'))]);
        }

        $mailer = $this->get('mail.mailer');
        if ($mailer === 'log') {
            config(['mail.default' => 'log']);
        } elseif ($mailer === 'smtp' && $this->get('mail.host')) {
            $encryption = (string) $this->get('mail.encryption', 'tls');
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => (string) $this->get('mail.host'),
                'mail.mailers.smtp.port' => (int) $this->get('mail.port', 587),
                'mail.mailers.smtp.username' => $this->get('mail.username'),
                'mail.mailers.smtp.password' => $this->rawPassword(),
                'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            ]);
        }
    }

    /** Sends one short message with the account as saved, so a wrong host or password is found now and not when a patient's report fails to send. */
    public function sendTest(string $to): ?string
    {
        try {
            app('mail.manager')->forgetMailers();
            $this->applyMail();
            Mail::raw(__('This is a test message from :app. If you can read it, outgoing e-mail works.', ['app' => config('app.name')]), function ($m) use ($to) {
                $m->to($to)->subject(__('Test message from :app', ['app' => config('app.name')]));
            });

            return null;
        } catch (\Throwable $e) {
            return Str::limit(str_replace((string) $this->rawPassword(), '***', $e->getMessage()), 300);
        }
    }

    private function storePassword(?string $plain): void
    {
        Setting::query()->updateOrCreate(['key' => 'mail.password'], [
            'group' => 'mail', 'type' => 'string', 'is_sensitive' => true,
            'value' => $plain === null ? null : Crypt::encryptString($plain),
        ]);
        $this->settings->flush();
    }

    private function rawPassword(): ?string
    {
        $stored = $this->settings->get('mail.password');
        if (! is_string($stored) || $stored === '') {
            return null;
        }
        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable) {
            return null;
        }
    }
}
