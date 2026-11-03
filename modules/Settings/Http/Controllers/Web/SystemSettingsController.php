<?php

namespace Modules\Settings\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\Branding\BrandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Name, logo, favicon, contact lines and the outgoing e-mail account, on one page (settings.view to look, settings.update to change). */
class SystemSettingsController extends Controller
{
    public function __construct(private readonly BrandingService $branding, private readonly AuditLogger $audit) {}

    public function show(Request $request)
    {
        $tab = in_array($request->query('tab'), ['general', 'branding', 'contact', 'email'], true) ? $request->query('tab') : 'general';

        return view('admin.settings.system', [
            'tab' => $tab,
            'v' => $this->branding->values(),
            'logo' => $this->branding->assetUrl('logo'),
            'favicon' => $this->branding->assetUrl('favicon'),
            'passwordSet' => $this->branding->mailPasswordIsSet(),
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:60'],
            'hospital_name' => ['nullable', 'string', 'max:120'],
            'hospital_phone' => ['nullable', 'string', 'max:40'],
            'hospital_email' => ['nullable', 'email', 'max:120'],
            'hospital_address' => ['nullable', 'string', 'max:255'],
            'hospital_website' => ['nullable', 'url', 'max:190'],
        ]);
        $this->branding->saveGeneral($data);

        return $this->done($request, 'general', $data);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:1024', Rule::dimensions()->minWidth(64)->minHeight(64)],
            'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:256'],
        ]);
        $changed = [];
        foreach (['logo', 'favicon'] as $kind) {
            if ($request->hasFile($kind)) {
                $this->branding->storeAsset($kind, $request->file($kind));
                $changed[$kind] = 'replaced';
            }
        }

        return $this->done($request, 'branding', $changed);
    }

    public function removeAsset(Request $request, string $kind): RedirectResponse
    {
        abort_unless(array_key_exists($kind, BrandingService::ASSETS), 404);
        $this->branding->removeAsset($kind);

        return $this->done($request, 'branding', [$kind => 'removed']);
    }

    public function updateContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'footer_text' => ['nullable', 'string', 'max:160'],
            'support_email' => ['nullable', 'email', 'max:120'],
            'support_phone' => ['nullable', 'string', 'max:40'],
            'support_url' => ['nullable', 'url', 'max:190'],
        ]);
        $this->branding->saveFooter($data);

        return $this->done($request, 'contact', $data);
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $smtp = $request->input('mailer') === 'smtp';
        $data = $request->validate([
            'mailer' => ['required', Rule::in(['smtp', 'log'])],
            'mail_host' => [$smtp ? 'required' : 'nullable', 'string', 'max:190'],
            'mail_port' => [$smtp ? 'required' : 'nullable', 'integer', 'between:1,65535'],
            'mail_encryption' => ['nullable', Rule::in(['none', 'tls', 'ssl'])],
            'mail_username' => ['nullable', 'string', 'max:190'],
            'mail_password' => ['nullable', 'string', 'max:190'],
            'clear_password' => ['nullable', 'boolean'],
            'mail_from_address' => ['required', 'email', 'max:190'],
            'mail_from_name' => ['required', 'string', 'max:80'],
        ]);

        return $this->done($request, 'email', $this->branding->saveMail($data));
    }

    public function testEmail(Request $request): RedirectResponse
    {
        $data = $request->validate(['test_to' => ['required', 'email', 'max:190']]);
        $this->branding->apply();
        $error = $this->branding->sendTest($data['test_to']);
        $this->audit->log('SETTINGS_MAIL_TEST', 'settings', null, null, ['to' => $data['test_to'], 'ok' => $error === null], $request);

        return redirect()->route('admin.settings.system', ['tab' => 'email'])->with($error === null ? 'success' : 'error', $error === null ? __('Test message sent to :to. Check that it arrives.', ['to' => $data['test_to']]) : __('The test message could not be sent: :why', ['why' => $error]));
    }

    /** @param  array<string, mixed>  $changes */
    private function done(Request $request, string $tab, array $changes): RedirectResponse
    {
        $this->audit->log('SETTINGS_UPDATE', 'settings', null, null, ['section' => $tab] + $changes, $request);

        return redirect()->route('admin.settings.system', ['tab' => $tab])->with('success', __('Saved.'));
    }
}
