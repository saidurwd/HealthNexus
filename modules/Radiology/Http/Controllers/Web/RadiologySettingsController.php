<?php

namespace Modules\Radiology\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RadiologySettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('radiology.settings.manage');

        $settings = $this->settings->all(true)['radiology'] ?? [];

        return view('admin.radiology.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        Gate::authorize('radiology.settings.manage');

        $validated = $request->validate([
            'settings' => ['array'],
            'settings.*' => ['nullable'],
        ]);

        $changes = [];

        foreach ($validated['settings'] ?? [] as $key => $value) {
            $fullKey = 'radiology.'.$key;
            $this->settings->set($fullKey, $value);
            $changes[$fullKey] = $value;
        }

        $this->auditLogger->log('SETTINGS_UPDATE', 'radiology_settings', null, null, $changes, $request);

        return redirect()->route('admin.radiology.settings.index')->with('success', 'Radiology settings updated successfully.');
    }
}
