<?php

namespace Modules\Quality\Http\Controllers\Web;

use App\Models\Department;
use App\Models\Patient;
use App\Models\Quality\QaIncident;
use App\Models\Quality\QaIncidentReporter;
use App\Models\Quality\QaInvestigation;
use App\Models\User;
use App\Services\Quality\ComplaintLinkService;
use App\Services\Quality\IncidentService;
use App\Services\Quality\InvestigationService;
use App\Services\Quality\QualityPolicy;
use App\Services\Quality\QualityScope;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Reporting (any staff), the list for the quality team, one incident with its trail, and the investigation. */
class IncidentController extends QualityController
{
    public function __construct(TenantContextResolver $tenant, private readonly IncidentService $incidents, private readonly InvestigationService $investigations, private readonly QualityPolicy $policy,
        private readonly QualityScope $scope, private readonly ComplaintLinkService $links)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'quality.incident.view');
        $f = $request->validate(['status' => ['nullable', 'string', 'max:14'], 'category' => ['nullable', 'string', 'max:24'], 'harm' => ['nullable', 'string', 'max:12'], 'q' => ['nullable', 'string', 'max:40'], 'overdue' => ['nullable', 'boolean']]);
        $query = $this->scope->apply(QaIncident::query(), $request->user(), $this->organizationId())->with('amendments')
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($f['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($f['harm'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('review_harm', $v)->orWhere(fn ($x) => $x->whereNull('review_harm')->where('harm', $v))))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where('number', 'like', '%'.$v.'%'))->when(! empty($f['overdue']), fn ($q) => $q->where('status', '!=', 'closed')->where('due_on', '<', today()->toDateString()))
            ->orderByRaw("case status when 'closed' then 1 else 0 end")->orderByDesc('occurred_at');

        return view('admin.quality.incidents.index', ['incidents' => $query->paginate(25)->withQueryString(), 'filters' => $f, 'policy' => $this->policy]);
    }

    public function create(Request $request)
    {
        $this->allow($request, 'quality.incident.report');

        return view('admin.quality.incidents.create', ['policy' => $this->policy, 'departments' => Department::query()->where('organization_id', $this->organizationId())->orderBy('name')->get(['id', 'name'])]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'quality.incident.report');
        $d = $request->validate(['kind' => ['required', 'string', 'max:14'], 'category' => ['required', 'string', 'max:24'], 'harm' => ['required', 'string', 'max:12'], 'occurred_at' => ['required', 'date'], 'description' => ['required', 'string', 'max:4000'],
            'immediate_action' => ['nullable', 'string', 'max:1000'], 'location' => ['nullable', 'string', 'max:120'], 'department_id' => ['nullable', 'integer'], 'patient_no' => ['nullable', 'string', 'max:40'], 'anonymous' => ['nullable', 'boolean']]);
        $patient = ! empty($d['patient_no']) ? Patient::query()->where('organization_id', $this->organizationId())->where('enterprise_patient_no', trim($d['patient_no']))->first() : null;
        if (! empty($d['patient_no']) && ! $patient) {
            throw ValidationException::withMessages(['patient_no' => __('No patient has that number.')]);
        }
        $incident = $this->incidents->report($this->organizationId(), $d + ['branch_id' => $this->branchId(), 'patient_id' => $patient?->id, 'anonymous' => (bool) ($d['anonymous'] ?? false)], $request->user());

        return redirect()->route('admin.quality.incidents.mine')->with('success', __('Thank you. Your report is :n. The quality team will look at it.', ['n' => $incident->number]));
    }

    public function mine(Request $request)
    {
        $this->allow($request, 'quality.incident.report');
        $ids = QaIncidentReporter::query()->where('user_id', $request->user()->id)->pluck('incident_id');

        return view('admin.quality.incidents.mine', ['incidents' => QaIncident::query()->with('amendments')->whereIn('id', $ids)->orderByDesc('occurred_at')->paginate(25)]);
    }

    public function show(Request $request, string $incident)
    {
        $i = $this->find(QaIncident::class, $incident);
        $own = $this->incidents->isReporter($i, $request->user());
        abort_unless($own || ($request->user()->can('quality.incident.view') && $this->scope->apply(QaIncident::query(), $request->user(), $this->organizationId())->whereKey($i->id)->exists()), 403);
        $i->load(['amendments.amender', 'events.user', 'investigation.lead', 'department', 'patient']);
        $manage = $request->user()->can('quality.incident.view');

        return view('admin.quality.incidents.show', ['i' => $i, 'own' => $own, 'manage' => $manage, 'policy' => $this->policy, 'actions' => $manage ? $i->actions()->with('owner')->get() : collect(), 'staff' => $manage ? $this->staff() : collect(),
            'needsInvestigation' => $this->incidents->needsInvestigation($i), 'overdue' => $this->incidents->overdue($i), 'complaints' => $manage ? $this->links->numbers($i) : [], 'service' => $this->incidents]);
    }

    public function triage(Request $request, string $incident)
    {
        $this->allow($request, 'quality.incident.triage');
        $d = $request->validate(['review_harm' => ['nullable', 'string', 'max:12'], 'owner_id' => ['nullable', 'integer'], 'due_on' => ['nullable', 'date']]);
        $i = $this->find(QaIncident::class, $incident);
        $owner = ! empty($d['owner_id']) ? User::query()->whereHas('organizations', fn ($q) => $q->where('organizations.id', $this->organizationId()))->find($d['owner_id']) : null;
        $this->incidents->triage($i, $request->user(), $d['review_harm'] ?? null, $owner, $d['due_on'] ?? null);

        return back()->with('success', __('Saved.'));
    }

    public function amend(Request $request, string $incident)
    {
        $i = $this->find(QaIncident::class, $incident);
        abort_unless($request->user()->can('quality.incident.amend') || $this->incidents->isReporter($i, $request->user()), 403);
        $d = $request->validate(['field' => ['required', 'string', 'max:30'], 'value' => ['nullable', 'string', 'max:4000'], 'reason' => ['required', 'string', 'max:500']]);
        $this->incidents->amend($i, $request->user(), $d['field'], $d['value'] ?? null, $d['reason']);

        return back()->with('success', __('The correction was added. The original report is kept.'));
    }

    public function close(Request $request, string $incident)
    {
        $this->allow($request, 'quality.incident.triage');
        $d = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        $this->incidents->close($this->find(QaIncident::class, $incident), $request->user(), $d['note']);

        return back()->with('success', __('The incident is closed.'));
    }

    public function reopen(Request $request, string $incident)
    {
        $this->allow($request, 'quality.incident.triage');
        $d = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->incidents->reopen($this->find(QaIncident::class, $incident), $request->user(), $d['reason']);

        return back()->with('success', __('The incident was reopened.'));
    }

    public function reveal(Request $request, string $incident)
    {
        $this->allow($request, 'quality.incident.reveal');
        $d = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $who = $this->incidents->reveal($this->find(QaIncident::class, $incident), $request->user(), $d['reason']);

        return back()->with($who ? 'success' : 'warning', $who ? __('Reported by :n. This was recorded.', ['n' => $who->name]) : __('This report was made anonymously: there is no one to show.'));
    }

    public function startInvestigation(Request $request, string $incident)
    {
        $this->allow($request, 'quality.investigation.manage');
        $d = $request->validate(['lead_id' => ['required', 'integer'], 'method' => ['required', 'string', 'max:16']]);
        $lead = User::query()->whereHas('organizations', fn ($q) => $q->where('organizations.id', $this->organizationId()))->findOrFail($d['lead_id']);
        $this->investigations->start($this->find(QaIncident::class, $incident), $lead, $d['method'], $request->user());

        return back()->with('success', __('The investigation has started.'));
    }

    public function saveInvestigation(Request $request, string $investigation)
    {
        $inv = $this->investigation($request, $investigation);
        $d = $request->validate(['findings' => ['nullable', 'string', 'max:6000'], 'cause_category' => ['nullable', 'array'], 'cause_category.*' => ['nullable', 'string', 'max:30'], 'cause_text' => ['nullable', 'array'], 'cause_text.*' => ['nullable', 'string', 'max:500']]);
        $causes = [];
        foreach (($d['cause_text'] ?? []) as $n => $text) {
            if (trim((string) $text) !== '') {
                $causes[] = ['category' => $d['cause_category'][$n] ?? '', 'text' => $text];
            }
        }
        $this->investigations->save($inv, $d['findings'] ?? null, $causes);

        return back()->with('success', __('Saved.'));
    }

    public function submitInvestigation(Request $request, string $investigation)
    {
        $this->investigations->submit($this->investigation($request, $investigation), $request->user());

        return back()->with('success', __('Handed in for review.'));
    }

    public function reviewInvestigation(Request $request, string $investigation)
    {
        $this->allow($request, 'quality.investigation.review');
        $d = $request->validate(['decision' => ['required', 'in:accept,return'], 'note' => ['nullable', 'string', 'max:500']]);
        $this->investigations->review($this->find(QaInvestigation::class, $investigation), $request->user(), $d['decision'] === 'accept', $d['note'] ?? null);

        return back()->with('success', __('Saved.'));
    }

    private function investigation(Request $request, string $publicId): QaInvestigation
    {
        $inv = $this->find(QaInvestigation::class, $publicId);
        abort_unless($request->user()->can('quality.investigation.manage') || $inv->lead_id === $request->user()->id, 403);

        return $inv;
    }
}
