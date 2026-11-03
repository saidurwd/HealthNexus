<?php

namespace Modules\Quality\Http\Controllers\Web;

use App\Models\Department;
use App\Models\Quality\QaAction;
use App\Models\Quality\QaAudit;
use App\Models\Quality\QaIndicator;
use App\Models\Quality\QaMortalityReview;
use App\Services\Quality\ActionService;
use App\Services\Quality\ClinicalAuditService;
use App\Services\Quality\IndicatorService;
use App\Services\Quality\MortalityService;
use App\Services\Quality\QualityOverview;
use App\Services\Quality\QualityPolicy;
use App\Services\Quality\QualityScope;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** Quality indicators, clinical audits and the review of deaths. */
class ProgrammeController extends QualityController
{
    public function __construct(TenantContextResolver $tenant, private readonly IndicatorService $indicators, private readonly QualityOverview $overview, private readonly ClinicalAuditService $audits,
        private readonly MortalityService $mortality, private readonly ActionService $actions, private readonly QualityScope $scope, private readonly QualityPolicy $policy)
    {
        parent::__construct($tenant);
    }

    public function indicators(Request $request)
    {
        $this->allow($request, 'quality.indicator.view');

        return view('admin.quality.indicators', ['rows' => $this->overview->latestIndicators($this->organizationId()), 'canManage' => $request->user()->can('quality.indicator.manage')]);
    }

    public function recompute(Request $request)
    {
        $this->allow($request, 'quality.indicator.manage');
        $n = $this->indicators->computeRecent($this->organizationId());

        return back()->with('success', __(':n value(s) worked out.', ['n' => $n]));
    }

    public function setTarget(Request $request, string $indicator)
    {
        $this->allow($request, 'quality.indicator.manage');
        $d = $request->validate(['target' => ['nullable', 'numeric', 'min:0', 'max:1000000']]);
        $i = QaIndicator::query()->where('public_id', $indicator)->where(fn ($q) => $q->whereNull('organization_id')->orWhere('organization_id', $this->organizationId()))->firstOrFail();
        $this->indicators->setTarget($this->organizationId(), $i, ($d['target'] ?? '') === '' ? null : (float) $d['target']);
        $this->indicators->computeRecent($this->organizationId());

        return back()->with('success', __('The target was saved.'));
    }

    public function audits(Request $request)
    {
        $this->allow($request, 'quality.audit.view');

        return view('admin.quality.audits.index', ['audits' => $this->scope->apply(QaAudit::query(), $request->user(), $this->organizationId())->orderByDesc('audited_on')->paginate(25), 'departments' => Department::query()->where('organization_id', $this->organizationId())->orderBy('name')->get(['id', 'name']),
            'canManage' => $request->user()->can('quality.audit.manage')]);
    }

    public function storeAudit(Request $request)
    {
        $this->allow($request, 'quality.audit.manage');
        $d = $request->validate(['title' => ['required', 'string', 'max:200'], 'standard' => ['nullable', 'string', 'max:500'], 'department_id' => ['nullable', 'integer'], 'audited_on' => ['nullable', 'date'], 'sample_size' => ['nullable', 'integer', 'min:0', 'max:100000']]);
        $audit = $this->audits->create($this->organizationId(), $d + ['branch_id' => $this->branchId()], $request->user());

        return redirect()->route('admin.quality.audits.show', $audit->public_id)->with('success', __('The audit was started. Record how often each criterion was met.'));
    }

    public function audit(Request $request, string $audit)
    {
        $this->allow($request, 'quality.audit.view');
        $a = $this->find(QaAudit::class, $audit);
        $a->load('findings', 'auditor');

        return view('admin.quality.audits.show', ['a' => $a, 'gaps' => $this->audits->gaps($a), 'canManage' => $request->user()->can('quality.audit.manage'), 'staff' => $this->staff(),
            'raised' => QaAction::query()->where(['source_type' => 'audit', 'source_id' => $a->id])->with('owner')->get()]);
    }

    public function recordAudit(Request $request, string $audit)
    {
        $this->allow($request, 'quality.audit.manage');
        $d = $request->validate(['criterion' => ['required', 'array', 'min:1'], 'criterion.*' => ['nullable', 'string', 'max:300'], 'met' => ['nullable', 'array'], 'met.*' => ['nullable', 'integer', 'min:0'], 'not_met' => ['nullable', 'array'], 'not_met.*' => ['nullable', 'integer', 'min:0'], 'comment' => ['nullable', 'array'], 'comment.*' => ['nullable', 'string', 'max:500']]);
        $rows = [];
        foreach ($d['criterion'] as $n => $criterion) {
            $rows[] = ['criterion' => $criterion, 'met' => $d['met'][$n] ?? 0, 'not_met' => $d['not_met'][$n] ?? 0, 'comment' => $d['comment'][$n] ?? null];
        }
        $this->audits->record($this->find(QaAudit::class, $audit), $rows);

        return back()->with('success', __('Saved.'));
    }

    public function completeAudit(Request $request, string $audit)
    {
        $this->allow($request, 'quality.audit.manage');
        $d = $request->validate(['summary' => ['nullable', 'string', 'max:4000'], 'gap_criterion' => ['nullable', 'array'], 'gap_criterion.*' => ['nullable', 'string', 'max:300'], 'gap_owner' => ['nullable', 'array'], 'gap_owner.*' => ['nullable', 'integer'], 'gap_due' => ['nullable', 'array'], 'gap_due.*' => ['nullable', 'date']]);
        $a = $this->audits->complete($this->find(QaAudit::class, $audit), $request->user(), $d['summary'] ?? null);
        $made = 0;
        foreach (($d['gap_criterion'] ?? []) as $n => $criterion) {
            if (! empty($d['gap_owner'][$n]) && $criterion) {
                $this->actions->create($this->organizationId(), ['source_type' => 'audit', 'source_id' => $a->id, 'action_type' => 'corrective', 'description' => __('Improve: :c', ['c' => $criterion]), 'owner_id' => $d['gap_owner'][$n], 'due_on' => $d['gap_due'][$n] ?? null, 'branch_id' => $a->branch_id], $request->user());
                $made++;
            }
        }

        return back()->with('success', __('The audit is complete. :n action(s) were raised.', ['n' => $made]));
    }

    public function mortality(Request $request)
    {
        $this->allow($request, 'quality.mortality.view');
        $status = $request->query('status', 'pending');

        return view('admin.quality.mortality.index', ['reviews' => $this->scope->apply(QaMortalityReview::query(), $request->user(), $this->organizationId())->with('patient', 'reviewer')->when($status !== 'all', fn ($q) => $q->where('status', $status))->orderByDesc('died_at')->paginate(25)->withQueryString(),
            'status' => $status, 'days' => $this->policy->int('indicator.mortality_review_days', 30), 'canReview' => $request->user()->can('quality.mortality.review')]);
    }

    public function findDeaths(Request $request)
    {
        $this->allow($request, 'quality.mortality.review');

        return back()->with('success', __(':n new death(s) to review.', ['n' => $this->mortality->sweep($this->organizationId())]));
    }

    public function review(Request $request, string $review)
    {
        $this->allow($request, 'quality.mortality.view');
        $r = $this->find(QaMortalityReview::class, $review);
        $r->load('patient', 'reviewer');

        return view('admin.quality.mortality.show', ['r' => $r, 'classes' => $this->policy->list('mortality.classifications'), 'canReview' => $request->user()->can('quality.mortality.review'), 'staff' => $this->staff(),
            'actions' => QaAction::query()->where(['source_type' => 'mortality', 'source_id' => $r->id])->with('owner')->get()]);
    }

    public function assignReview(Request $request, string $review)
    {
        $this->allow($request, 'quality.mortality.review');
        $d = $request->validate(['reviewer_id' => ['required', 'integer']]);
        $reviewer = $this->staff()->firstWhere('id', (int) $d['reviewer_id']) ?? abort(404);
        $this->mortality->assign($this->find(QaMortalityReview::class, $review), $reviewer, $request->user());

        return back()->with('success', __('Saved.'));
    }

    public function doReview(Request $request, string $review)
    {
        $this->allow($request, 'quality.mortality.review');
        $d = $request->validate(['classification' => ['required', 'string', 'max:26'], 'findings' => ['required', 'string', 'max:6000'], 'learning' => ['nullable', 'string', 'max:1000'], 'action' => ['nullable', 'string', 'max:1000'], 'owner_id' => ['nullable', 'integer'], 'due_on' => ['nullable', 'date']]);
        $r = $this->mortality->review($this->find(QaMortalityReview::class, $review), $request->user(), $d['classification'], $d['findings'], $d['learning'] ?? null);
        if (! empty($d['action']) && ! empty($d['owner_id'])) {
            $this->actions->create($this->organizationId(), ['source_type' => 'mortality', 'source_id' => $r->id, 'action_type' => 'improvement', 'description' => $d['action'], 'owner_id' => $d['owner_id'], 'due_on' => $d['due_on'] ?? null, 'branch_id' => $r->branch_id], $request->user());
        }

        return back()->with('success', __('The review was saved.'));
    }
}
