<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NursingSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('nursing.settings.manage');

        $settings = $this->settings->all(true)['nursing'] ?? [];

        return view('admin.nursing.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        Gate::authorize('nursing.settings.manage');

        $validated = $request->validate([
            'settings' => ['array'],
            'settings.*' => ['nullable'],
        ]);

        $changes = [];

        foreach ($validated['settings'] ?? [] as $key => $value) {
            $fullKey = 'nursing.'.$key;

            if (is_array($this->settings->get($fullKey))) {
                $decoded = json_decode((string) $value, true);

                if (! is_array($decoded)) {
                    return back()->withErrors(["settings.$key" => "'$key' must be valid JSON."])->withInput();
                }

                $value = $decoded;
            }

            $this->settings->set($fullKey, $value);
            $changes[$fullKey] = $value;
        }

        $this->auditLogger->log('SETTINGS_UPDATE', 'nursing_settings', null, null, $changes, $request);

        return redirect()->route('admin.nursing.settings.index')->with('success', 'Nursing settings updated successfully.');
    }
}
