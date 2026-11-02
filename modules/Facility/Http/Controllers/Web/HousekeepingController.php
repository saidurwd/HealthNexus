<?php

namespace Modules\Facility\Http\Controllers\Web;

use App\Models\Facility\FacCleaningAudit;
use App\Models\Facility\FacCleaningTask;
use App\Services\Facility\FacilityPolicy;
use App\Services\Facility\HousekeepingService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** The cleaning board: tasks waiting, working through a checklist, inspection, and cleaning audits. */
class HousekeepingController extends FacilityController
{
    public function __construct(TenantContextResolver $tenant, private readonly HousekeepingService $housekeeping, private readonly FacilityPolicy $policy)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'facility.housekeeping.view');
        $show = $request->query('show', 'waiting');
        $tasks = FacCleaningTask::query()->with('assignee')->where('organization_id', $this->organizationId())->when($show === 'waiting', fn ($q) => $q->whereIn('status', ['pending', 'in_progress', 'done']))->when($show === 'inspect', fn ($q) => $q->where('status', 'done')->where('bed_released', false))
            ->when($show === 'finished', fn ($q) => $q->whereIn('status', ['inspected', 'done'])->where('bed_released', true))->orderByRaw("case priority when 'urgent' then 0 else 1 end")->orderBy('requested_at')->paginate(30)->withQueryString();

        return view('admin.facility.housekeeping.index', ['tasks' => $tasks, 'show' => $show, 'target' => $this->policy->int('housekeeping.turnover_target_minutes', 45), 'kinds' => array_keys((array) $this->policy->get('housekeeping.checklists', [])),
            'can' => ['work' => $request->user()->can('facility.housekeeping.work'), 'inspect' => $request->user()->can('facility.housekeeping.inspect'), 'manage' => $request->user()->can('facility.housekeeping.manage')], 'me' => $request->user()->id]);
    }

    public function request(Request $request)
    {
        $this->allow($request, 'facility.housekeeping.manage', 'facility.housekeeping.work');
        $d = $request->validate(['kind' => ['required', 'string', 'max:20'], 'location' => ['required', 'string', 'max:120']]);
        $this->housekeeping->request($this->organizationId(), $d['kind'], $d['location'], $request->user(), $this->branchId());

        return back()->with('success', __('The task was added.'));
    }

    public function start(Request $request, string $task)
    {
        $this->allow($request, 'facility.housekeeping.work');
        $this->housekeeping->start($this->find(FacCleaningTask::class, $task), $request->user());

        return back()->with('success', __('Started.'));
    }

    public function complete(Request $request, string $task)
    {
        $this->allow($request, 'facility.housekeeping.work');
        $d = $request->validate(['done' => ['nullable', 'array'], 'done.*' => ['integer'], 'note' => ['nullable', 'string', 'max:500']]);
        $t = $this->housekeeping->complete($this->find(FacCleaningTask::class, $task), $request->user(), $d['done'] ?? [], $d['note'] ?? null);

        return back()->with('success', $t->bed_released ? __('Done. The bed is available again.') : __('Done. It now needs inspecting.'));
    }

    public function inspect(Request $request, string $task)
    {
        $this->allow($request, 'facility.housekeeping.inspect');
        $d = $request->validate(['result' => ['required', 'in:pass,redo'], 'note' => ['nullable', 'string', 'max:500']]);
        $this->housekeeping->inspect($this->find(FacCleaningTask::class, $task), $request->user(), $d['result'] === 'pass', $d['note'] ?? null);

        return back()->with('success', __('Saved.'));
    }

    public function cancel(Request $request, string $task)
    {
        $this->allow($request, 'facility.housekeeping.manage');
        $d = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        $this->housekeeping->cancel($this->find(FacCleaningTask::class, $task), $d['reason']);

        return back()->with('success', __('The task was cancelled.'));
    }

    public function audits(Request $request)
    {
        $this->allow($request, 'facility.housekeeping.view');

        return view('admin.facility.housekeeping.audits', ['audits' => FacCleaningAudit::query()->with('auditor')->where('organization_id', $this->organizationId())->orderByDesc('audited_on')->paginate(25), 'items' => (array) $this->policy->get('housekeeping.checklists.room', []),
            'pass' => $this->policy->int('housekeeping.audit_pass_percent', 85), 'canManage' => $request->user()->can('facility.housekeeping.manage') || $request->user()->can('facility.housekeeping.inspect')]);
    }

    public function storeAudit(Request $request)
    {
        $this->allow($request, 'facility.housekeeping.manage', 'facility.housekeeping.inspect');
        $d = $request->validate(['area' => ['required', 'string', 'max:120'], 'items' => ['required', 'array', 'min:1'], 'items.*' => ['string', 'max:200'], 'ok' => ['nullable', 'array'], 'ok.*' => ['integer']]);
        $results = [];
        foreach ($d['items'] as $i => $item) {
            $results[] = ['item' => $item, 'ok' => in_array($i, array_map('intval', $d['ok'] ?? []), true)];
        }
        $a = $this->housekeeping->audit($this->organizationId(), $d['area'], $results, $request->user(), null, $this->branchId());

        return back()->with('success', __('Scored :s%.', ['s' => (float) $a->score]));
    }
}
