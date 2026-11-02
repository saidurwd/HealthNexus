<?php

namespace Modules\Ai\Http\Controllers\Web;

use App\Models\Ai\AiModel;
use App\Models\Ai\AiUseCase;
use App\Services\Ai\AiInstaller;
use App\Services\Ai\AiPolicy;
use App\Services\Ai\Monitoring;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class AiHomeController extends AiController
{
    public function __construct(TenantContextResolver $tenant, private readonly Monitoring $monitoring, private readonly AiInstaller $installer, private readonly AiPolicy $policy)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'ai.use', 'ai.dashboard.view');
        $org = $this->organizationId();
        $useCases = AiUseCase::query()->with('model')->where('organization_id', $org)->orderBy('implemented', 'desc')->orderBy('id')->get();

        return view('admin.ai.index', ['useCases' => $useCases, 'metrics' => $useCases->where('status', 'enabled')->mapWithKeys(fn ($u) => [$u->code => $this->monitoring->metrics($org, $u->code)]), 'on' => $this->policy->bool('enabled', true),
            'hasModel' => AiModel::query()->where('organization_id', $org)->where('status', 'approved')->exists(), 'can' => ['start' => $request->user()->can('ai.model.approve') && $request->user()->can('ai.usecase.approve'), 'use' => $request->user()->can('ai.use'), 'kill' => $request->user()->can('ai.killswitch')]]);
    }

    /** Set up the local engine (no model, no network), evaluate it and switch on the low-risk functions. */
    public function startLocal(Request $request)
    {
        $this->allow($request, 'ai.model.approve');
        $this->allow($request, 'ai.usecase.approve');
        $r = $this->installer->startLocal($this->organizationId(), $request->user(), $request->user());

        return back()->with($r['passed'] ? 'success' : 'warning', $r['passed'] ? __('The local engine passed its evaluation. :n function(s) are switched on.', ['n' => $r['enabled']]) : __('The local engine did not pass its evaluation, so nothing was switched on.'));
    }
}
