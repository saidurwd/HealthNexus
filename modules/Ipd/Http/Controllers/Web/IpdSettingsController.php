<?php

namespace Modules\Ipd\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IpdSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('ipd.settings.manage');

        $settings = $this->settings->all(true)['ipd'] ?? [];

        return view('admin.ipd.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        Gate::authorize('ipd.settings.manage');

        $validated = $request->validate([
            'settings' => ['array'],
            'settings.*' => ['nullable'],
        ]);

        $changes = [];

        foreach ($validated['settings'] ?? [] as $key => $value) {
            $fullKey = 'ipd.'.$key;
            $this->settings->set($fullKey, $value);
            $changes[$fullKey] = $value;
        }

        $this->auditLogger->log('SETTINGS_UPDATE', 'ipd_settings', null, null, $changes, $request);

        return redirect()->route('admin.ipd.settings.index')->with('success', 'IPD settings updated successfully.');
    }
}
