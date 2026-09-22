<?php

namespace Modules\Settings\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settings,
        private AuditLogger $auditLogger
    ) {}

    public function index(Request $request)
    {
        $settings = $this->settings->all(true);

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => ['array'],
            'settings.*' => ['nullable'],
        ]);

        $changes = [];

        foreach ($validated['settings'] ?? [] as $key => $value) {
            $key = (string) $key;

            $this->settings->set($key, $value);

            $changes[$key] = $value;
        }

        $this->auditLogger->log('SETTINGS_UPDATE', 'settings', null, null, $changes, $request);

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully.');
    }
}
