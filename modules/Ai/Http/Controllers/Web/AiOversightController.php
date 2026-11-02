<?php

namespace Modules\Ai\Http\Controllers\Web;

use App\Models\Ai\AiIncident;
use App\Models\Ai\AiRequest;
use App\Models\Ai\AiUseCase;
use App\Models\User;
use App\Services\Ai\AiIncidents;
use App\Services\Ai\AiPolicy;
use App\Services\Ai\Monitoring;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** Watching AI: incidents, monitoring, the request log, and the master switch. */
class AiOversightController extends AiController
{
    public function __construct(TenantContextResolver $tenant, private readonly AiIncidents $incidents, private readonly Monitoring $monitoring, private readonly AiPolicy $policy, private readonly SettingsService $settings)
    {
        parent::__construct($tenant);
    }

    public function incidents(Request $request)
    {
        $this->allow($request, 'ai.incident.view', 'ai.incident.manage');
        $status = $request->query('status', 'open');
        $list = AiIncident::query()->where('organization_id', $this->organizationId())->when($status !== 'all', fn ($q) => $q->where('status', $status === 'open' ? '!=' : '=', $status === 'open' ? 'closed' : $status))->orderByDesc('id')->paginate(25)->withQueryString();

        return view('admin.ai.incidents', ['incidents' => $list, 'status' => $status, 'staff' => $this->staff(), 'canManage' => $request->user()->can('ai.incident.manage'), 'codes' => AiUseCase::query()->where('organization_id', $this->organizationId())->pluck('code')]);
    }

    public function report(Request $request)
    {
        $this->allow($request, 'ai.use', 'ai.incident.manage');
        $d = $request->validate(['kind' => ['required', 'string', 'max:16'], 'severity' => ['required', 'string', 'max:8'], 'use_case_code' => ['nullable', 'string', 'max:40'], 'description' => ['required', 'string', 'min:10', 'max:2000']]);
        $this->incidents->open($this->organizationId(), $d['kind'], $d['severity'], $d['use_case_code'] ?? null, null, $d['description'], $request->user());

        return back()->with('success', __('Reported. Thank you.'));
    }

    public function assign(Request $request, string $incident)
    {
        $this->allow($request, 'ai.incident.manage');
        $d = $request->validate(['owner_id' => ['required', 'integer']]);
        $this->incidents->assign($this->find(AiIncident::class, $incident), $this->staff()->firstWhere('id', (int) $d['owner_id']) ?? abort(404));

        return back()->with('success', __('Saved.'));
    }

    public function close(Request $request, string $incident)
    {
        $this->allow($request, 'ai.incident.manage');
        $d = $request->validate(['resolution' => ['required', 'string', 'max:500']]);
        $this->incidents->close($this->find(AiIncident::class, $incident), $request->user(), $d['resolution']);

        return back()->with('success', __('The incident is closed.'));
    }

    public function monitoring(Request $request)
    {
        $this->allow($request, 'ai.monitor.view');
        $org = $this->organizationId();
        $days = (int) $this->policy->int('monitoring.window_days', 30);

        return view('admin.ai.monitoring', ['rows' => AiUseCase::query()->where('organization_id', $org)->where('implemented', true)->get()->map(fn ($u) => ['uc' => $u, 'm' => $this->monitoring->metrics($org, $u->code, $days)]), 'days' => $days]);
    }

    public function audit(Request $request)
    {
        $this->allow($request, 'ai.audit.view');
        $f = $request->validate(['outcome' => ['nullable', 'string', 'max:10'], 'use_case' => ['nullable', 'string', 'max:40']]);
        $rows = AiRequest::query()->where('organization_id', $this->organizationId())->when($f['outcome'] ?? null, fn ($q, $v) => $q->where('outcome', $v))->when($f['use_case'] ?? null, fn ($q, $v) => $q->where('use_case_code', $v))->orderByDesc('id')->paginate(30)->withQueryString();

        return view('admin.ai.audit', ['rows' => $rows, 'filters' => $f, 'users' => User::query()->whereIn('id', $rows->pluck('user_id')->filter())->pluck('name', 'id')]);
    }

    public function killSwitch(Request $request)
    {
        $this->allow($request, 'ai.killswitch');
        $d = $request->validate(['state' => ['required', 'in:on,off'], 'reason' => ['nullable', 'string', 'max:300']]);
        if ($d['state'] === 'off' && mb_strlen(trim((string) ($d['reason'] ?? ''))) < 5) {
            return back()->with('warning', __('Say why AI is being switched off.'));
        }
        $this->settings->set('ai.enabled', $d['state'] === 'on' ? 'true' : 'false');
        app(AuditLogger::class)->log('UPDATE', 'AiSwitch', null, null, ['state' => $d['state'], 'reason' => $d['reason'] ?? null]);

        return back()->with($d['state'] === 'on' ? 'success' : 'warning', $d['state'] === 'on' ? __('AI is switched on.') : __('AI is switched off for the whole hospital.'));
    }
}
