<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LabSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('lab.settings.manage');

        $settings = $this->settings->all(true)['laboratory'] ?? [];

        return view('admin.lab.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        Gate::authorize('lab.settings.manage');

        $validated = $request->validate([
            'settings' => ['array'],
            'settings.*' => ['nullable'],
        ]);

        $changes = [];

        foreach ($validated['settings'] ?? [] as $key => $value) {
            $fullKey = 'laboratory.'.$key;
            $this->settings->set($fullKey, $value);
            $changes[$fullKey] = $value;
        }

        $this->auditLogger->log('SETTINGS_UPDATE', 'laboratory_settings', null, null, $changes, $request);

        return redirect()->route('admin.lab.settings.index')->with('success', 'Laboratory settings updated successfully.');
    }
}
