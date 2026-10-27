<?php

namespace Modules\Insurance\Http\Controllers\Web;

use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Insurance\InsuranceEligibilityCheck;
use App\Models\Insurance\InsurancePayer;
use App\Models\Insurance\InsurancePlan;
use App\Models\Insurance\InsurancePreauth;
use App\Models\Patient;
use App\Services\Insurance\InsuranceEligibilityService;
use App\Services\Insurance\InsuranceMasterService;
use App\Services\Insurance\InsurancePolicyService;
use App\Services\Insurance\InsurancePreauthService;
use App\Services\Insurance\InsuranceQueryService;
use App\Services\Insurance\InsuranceReportService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** The dashboard, payers and plans, policies with eligibility, pre-authorizations, and the denial worklist. */
class InsuranceDeskController extends InsuranceController
{
    public function __construct(TenantContextResolver $tenant, private readonly InsuranceMasterService $masters, private readonly InsurancePolicyService $policies, private readonly InsuranceEligibilityService $eligibility, private readonly InsurancePreauthService $preauths)
    {
        parent::__construct($tenant);
    }

    public function dashboard(Request $request, InsuranceReportService $reports, InsuranceQueryService $queries)
    {
        $this->allow($request, 'dashboard.view');

        return view('admin.insurance.dashboard', ['d' => $reports->dashboard($this->organizationId()), 'aging' => $reports->aging($this->organizationId()), 'worklist' => $queries->worklist($this->organizationId())]);
    }

    public function payers(Request $request)
    {
        $this->allow($request, 'payer.view');

        return view('admin.insurance.payers', ['payers' => InsurancePayer::query()->with('plans')->where('organization_id', $this->organizationId())->orderBy('name')->get(), 'types' => config('insurance.payer_types'), 'methods' => config('insurance.submission_methods')]);
    }

    public function storePayer(Request $request): RedirectResponse
    {
        $this->allow($request, 'payer.manage');
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30'], 'name' => ['required', 'string', 'max:150'], 'payer_type' => ['required', 'in:'.implode(',', config('insurance.payer_types'))],
            'submission_method' => ['required', 'in:'.implode(',', config('insurance.submission_methods'))], 'filing_deadline_days' => ['nullable', 'integer', 'between:1,730'], 'payment_terms_days' => ['nullable', 'integer', 'between:0,365'],
        ]);
        $this->masters->createPayer($this->organizationId(), $validated);

        return back()->with('success', __('Payer added.'));
    }

    public function storePlan(Request $request, InsurancePayer $payer): RedirectResponse
    {
        $this->scoped($request, $payer, 'plan.manage');
        $validated = $request->validate(['code' => ['required', 'string', 'max:30'], 'name' => ['required', 'string', 'max:150'], 'copay_percent' => ['required', 'numeric', 'between:0,100'], 'deductible_amount' => ['nullable', 'numeric', 'min:0'], 'annual_limit' => ['nullable', 'numeric', 'min:0'], 'per_visit_limit' => ['nullable', 'numeric', 'min:0']]);
        $this->masters->createPlan($payer, $validated + ['deductible_amount' => $validated['deductible_amount'] ?? 0]);

        return back()->with('success', __('Plan added.'));
    }

    public function policies(Request $request)
    {
        $this->allow($request, 'policy.view');
        $term = trim((string) $request->query('q'));
        $policies = BillingInsurancePolicy::query()->where('organization_id', $this->organizationId())->whereNotNull('payer_id')
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('policy_number', 'like', "%$term%")->orWhere('member_number', 'like', "%$term%")->orWhereIn('patient_id', Patient::query()->where('organization_id', $this->organizationId())->where(fn ($p) => $p->where('first_name', 'like', "%$term%")->orWhere('last_name', 'like', "%$term%")->orWhere('enterprise_patient_no', 'like', "%$term%"))->select('id'))))
            ->latest('id')->paginate(25)->withQueryString();

        return view('admin.insurance.policies', ['policies' => $policies, 'q' => $term, 'payers' => InsurancePayer::where('organization_id', $this->organizationId())->with('plans')->orderBy('name')->get(),
            'patients' => Patient::query()->whereIn('id', $policies->pluck('patient_id'))->get(['id', 'first_name', 'last_name', 'enterprise_patient_no', 'public_id'])->keyBy('id'),
            'latest' => InsuranceEligibilityCheck::query()->whereIn('policy_id', $policies->pluck('id'))->latest('checked_at')->get()->unique('policy_id')->keyBy('policy_id'), 'payerNames' => InsurancePayer::where('organization_id', $this->organizationId())->pluck('name', 'id'), 'planNames' => InsurancePlan::where('organization_id', $this->organizationId())->pluck('name', 'id')]);
    }

    public function storePolicy(Request $request): RedirectResponse
    {
        $this->allow($request, 'policy.manage');
        $validated = $request->validate([
            'patient' => ['required', 'string', 'max:40'], 'plan' => ['required', 'string', 'size:26'], 'policy_number' => ['required', 'string', 'max:60'], 'member_number' => ['required', 'string', 'max:60'],
            'effective_from' => ['required', 'date'], 'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'], 'coverage_order' => ['nullable', 'integer', 'between:1,3'],
        ]);
        $org = $this->organizationId();
        $patient = Patient::query()->where('organization_id', $org)->where(fn ($q) => $q->where('public_id', $validated['patient'])->orWhere('enterprise_patient_no', $validated['patient']))->firstOrFail();
        $plan = InsurancePlan::query()->where('organization_id', $org)->where('public_id', $validated['plan'])->firstOrFail();
        $this->policies->attach($patient, InsurancePayer::findOrFail($plan->payer_id), $plan, collect($validated)->only(['policy_number', 'member_number', 'effective_from', 'effective_to', 'coverage_order'])->filter()->all());

        return back()->with('success', __('Policy added.'));
    }

    public function verify(Request $request, BillingInsurancePolicy $policy): RedirectResponse
    {
        $this->scoped($request, $policy, 'eligibility.verify');
        $validated = $request->validate(['status' => ['required', 'in:eligible,not_eligible,unknown,adapter'], 'note' => ['nullable', 'string', 'max:300']]);
        $patient = Patient::findOrFail($policy->patient_id);
        $check = $validated['status'] === 'adapter'
            ? $this->eligibility->verifyViaAdapter($policy, $patient, $request->user())
            : $this->eligibility->verifyManually($policy, $patient, $request->user(), $validated['status'], $validated['note'] ?? null);

        return back()->with($check->status === 'eligible' ? 'success' : 'warning', __('Recorded: :s', ['s' => __(Str::headline($check->status))]));
    }

    public function preauths(Request $request)
    {
        $this->allow($request, 'preauth.view');

        return view('admin.insurance.preauths', ['preauths' => InsurancePreauth::query()->where('organization_id', $this->organizationId())->orderByRaw("status = 'requested' desc")->latest('requested_at')->paginate(25)]);
    }

    public function requestPreauth(Request $request): RedirectResponse
    {
        $this->allow($request, 'preauth.request');
        $validated = $request->validate(['policy' => ['required', 'string', 'size:26'], 'service_description' => ['required', 'string', 'max:300'], 'requested_amount' => ['required', 'numeric', 'min:1']]);
        $policy = BillingInsurancePolicy::query()->where('organization_id', $this->organizationId())->where('public_id', $validated['policy'])->firstOrFail();
        $this->preauths->request($policy, Patient::findOrFail($policy->patient_id), $request->user(), $validated['service_description'], number_format((float) $validated['requested_amount'], 2, '.', ''));

        return back()->with('success', __('Pre-authorization requested.'));
    }

    public function decidePreauth(Request $request, InsurancePreauth $preauth): RedirectResponse
    {
        $this->scoped($request, $preauth, 'preauth.decide');
        $validated = $request->validate(['status' => ['required', 'in:approved,partial,rejected'], 'authorized_amount' => ['nullable', 'numeric', 'min:0'], 'payer_reference' => ['nullable', 'string', 'max:80'], 'note' => ['nullable', 'string', 'max:300']]);
        $this->preauths->decide($preauth, $validated['status'], number_format((float) ($validated['authorized_amount'] ?? 0), 2, '.', ''), $validated['payer_reference'] ?? null, null, null, $validated['note'] ?? null);

        return back()->with('success', __('Decision recorded.'));
    }

    public function worklist(Request $request, InsuranceQueryService $queries)
    {
        $this->allow($request, 'claim.view');

        return view('admin.insurance.worklist', ['worklist' => $queries->worklist($this->organizationId())]);
    }
}
