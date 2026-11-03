<?php

namespace Modules\Watch\Http\Controllers\Web;

use App\Models\Watch\SvCondition;
use App\Services\Watch\SurveillanceService;
use App\Services\Watch\WatchInstaller;
use Illuminate\Http\Request;

class ConditionController extends WatchController
{
    public function index(Request $request)
    {
        $this->allow($request, 'watch.signal.view');

        return view('admin.watch.conditions', ['rows' => SvCondition::query()->where('organization_id', $this->organizationId())->orderBy('name')->get(), 'canManage' => $request->user()->can('watch.condition.manage')]);
    }

    public function update(Request $request, SurveillanceService $surveillance, string $condition)
    {
        $this->allow($request, 'watch.condition.manage');
        $v = $request->validate(['window_days' => ['required', 'integer'], 'min_cases' => ['required', 'integer'], 'spike_factor' => ['required', 'numeric'], 'cluster_cases' => ['required', 'integer'], 'severity' => ['required', 'string'], 'active' => ['nullable', 'boolean']]);
        $c = SvCondition::query()->where('organization_id', $this->organizationId())->findOrFail((int) $condition);
        $surveillance->updateCondition($c, $v + ['active' => $request->boolean('active')]);

        return back()->with('success', __('Saved.'));
    }

    public function install(Request $request, WatchInstaller $installer)
    {
        $this->allow($request, 'watch.condition.manage');
        $r = $installer->install($this->organizationId());

        return back()->with('success', __(':n condition(s) added.', ['n' => $r['conditions']]));
    }
}
