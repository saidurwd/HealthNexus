<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BillingSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('billing.settings.manage');

        $settings = $this->settings->all(true)['billing'] ?? [];

        return view('admin.billing.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        Gate::authorize('billing.settings.manage');

        $validated = $request->validate([
            'settings' => ['array'],
            'settings.*' => ['nullable'],
        ]);

        $changes = [];

        foreach ($validated['settings'] ?? [] as $key => $value) {
            $fullKey = 'billing.'.$key;
            $this->settings->set($fullKey, $value);
            $changes[$fullKey] = $value;
        }

        $this->auditLogger->log('SETTINGS_UPDATE', 'billing_settings', null, null, $changes, $request);

        return redirect()->route('admin.billing.settings.index')->with('success', 'Billing settings updated successfully.');
    }
}
