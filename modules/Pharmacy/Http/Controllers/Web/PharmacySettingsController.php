<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PharmacySettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('pharmacy.settings.manage');

        $settings = $this->settings->all(true)['pharmacy'] ?? [];

        return view('admin.pharmacy.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        Gate::authorize('pharmacy.settings.manage');

        $validated = $request->validate([
            'settings' => ['array'],
            'settings.*' => ['nullable'],
        ]);

        $changes = [];

        foreach ($validated['settings'] ?? [] as $key => $value) {
            $fullKey = 'pharmacy.'.$key;
            $this->settings->set($fullKey, $value);
            $changes[$fullKey] = $value;
        }

        $this->auditLogger->log('SETTINGS_UPDATE', 'pharmacy_settings', null, null, $changes, $request);

        return redirect()->route('admin.pharmacy.settings.index')->with('success', 'Pharmacy settings updated successfully.');
    }
}
