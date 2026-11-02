<?php

namespace Modules\Welfare\Http\Controllers\Web;

use App\Models\User;
use App\Models\Welfare\WfAssessment;
use App\Models\Welfare\WfFund;
use App\Services\TenantContextResolver;
use App\Services\Welfare\AssessmentService;
use Illuminate\Http\Request;

/** The means test. Income and circumstances are sensitive: every screen here needs the assessment right. */
class AssessmentController extends WelfareController
{
    public function __construct(TenantContextResolver $tenant, private readonly AssessmentService $assessments)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'welfare.assessment.view');
        $status = $request->query('status', 'submitted');
        $q = WfAssessment::query()->where('organization_id', $this->organizationId())->with('patient')->when($status !== 'all', fn ($w) => $w->where('status', $status))->orderByDesc('id');

        return view('admin.welfare.assessments', ['rows' => $q->paginate(25)->withQueryString(), 'status' => $status, 'factors' => array_keys(config('welfare.factors')), 'canCreate' => $request->user()->can('welfare.assessment.create')]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'welfare.assessment.create');
        $d = $request->validate(['patient_no' => ['required', 'string', 'max:40'], 'monthly_income' => ['required', 'numeric', 'min:0'], 'household_size' => ['required', 'integer', 'min:1', 'max:40'], 'occupation' => ['nullable', 'string', 'max:80'],
            'factors' => ['nullable', 'array'], 'factors.*' => ['string', 'max:30'], 'evidence_note' => ['nullable', 'string', 'max:500']]);
        $a = $this->assessments->start($this->patientByNumber($d['patient_no']), $d, $request->user());

        return redirect()->route('admin.welfare.assessments.show', $a->public_id)->with('success', __('The assessment was started.'));
    }

    public function show(Request $request, string $assessment)
    {
        $this->allow($request, 'welfare.assessment.view');
        $a = $this->find(WfAssessment::class, $assessment)->load('patient', 'grants.fund');
        $u = $request->user();

        return view('admin.welfare.assessment', ['a' => $a, 'funds' => WfFund::query()->where('organization_id', $this->organizationId())->where('status', 'active')->orderBy('name')->get(), 'canSubmit' => $u->can('welfare.assessment.create') && $a->status === 'draft',
            'canDecide' => $u->can('welfare.assessment.decide') && $a->status === 'submitted' && $a->assessed_by !== $u->id, 'canRequest' => $u->can('welfare.grant.request') && $a->status === 'approved' && $a->category !== 'none',
            'assessor' => $a->assessed_by ? User::query()->find($a->assessed_by)?->name : null]);
    }

    public function submit(Request $request, string $assessment)
    {
        $this->allow($request, 'welfare.assessment.create');
        $a = $this->find(WfAssessment::class, $assessment);
        $d = $request->validate(['evidence_note' => ['nullable', 'string', 'max:500']]);
        if (! empty($d['evidence_note'])) {
            $a->update(['evidence_note' => $d['evidence_note']]);
        }
        $this->assessments->submit($a->refresh(), $request->user());

        return back()->with('success', __('Sent to the committee.'));
    }

    public function decide(Request $request, string $assessment)
    {
        $this->allow($request, 'welfare.assessment.decide');
        $d = $request->validate(['decision' => ['required', 'in:approve,reject'], 'note' => ['nullable', 'string', 'max:300']]);
        $this->assessments->decide($this->find(WfAssessment::class, $assessment), $request->user(), $d['decision'] === 'approve', $d['note'] ?? null);

        return back()->with('success', $d['decision'] === 'approve' ? __('The assessment is approved.') : __('The assessment is turned down.'));
    }
}
