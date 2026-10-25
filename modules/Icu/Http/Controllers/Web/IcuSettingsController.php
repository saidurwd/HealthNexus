<?php

namespace Modules\Icu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IcuSettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function index()
    {
        Gate::authorize('icu.settings.manage');

        $settings = [
            'admission_requires_approval' => $this->settings->get('icu.admission_requires_approval', true),
            'scoring_definitions' => $this->settings->get('icu.scoring_definitions', []),
            'alert_threshold_rules' => $this->settings->get('icu.alert_threshold_rules', []),
            'discharge_destinations' => $this->settings->get('icu.discharge_destinations', []),
        ];

        return view('admin.icu.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        Gate::authorize('icu.settings.manage');

        $validated = $request->validate(['admission_requires_approval' => ['nullable', 'boolean']]);

        $this->settings->set('icu.admission_requires_approval', (bool) ($validated['admission_requires_approval'] ?? false));

        return redirect()->route('admin.icu.settings.index')->with('success', __('ICU settings updated.'));
    }
}
