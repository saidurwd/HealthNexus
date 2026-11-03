<?php

namespace Modules\Welfare\Http\Controllers\Web;

use App\Models\Billing\BillingInvoice;
use App\Models\Welfare\WfApplication;
use App\Models\Welfare\WfAssessment;
use App\Models\Welfare\WfFund;
use App\Models\Welfare\WfGrant;
use App\Services\TenantContextResolver;
use App\Services\Welfare\ApplicationService;
use App\Services\Welfare\GrantService;
use App\Support\Dashboard\DashboardPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GrantController extends WelfareController
{
    public function __construct(TenantContextResolver $tenant, private readonly GrantService $grants, private readonly ApplicationService $applications)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'welfare.grant.view');
        $status = $request->query('status', 'requested');
        $q = WfGrant::query()->where('organization_id', $this->organizationId())->with('patient', 'fund')->when($status !== 'all', fn ($w) => $w->where('status', $status))->orderByDesc('id');

        return view('admin.welfare.grants', ['rows' => $q->paginate(25)->withQueryString(), 'status' => $status]);
    }

    /** The discounts behind the dashboard's "given" figures: the same period, the same rows. */
    public function discounts(Request $request)
    {
        $this->allow($request, 'welfare.grant.view');
        $period = DashboardPeriod::fromRequest($request, '30d', ['7d', '30d', '90d', 'month', 'last_month', 'custom']);
        $status = $request->query('status', 'applied');
        $q = WfApplication::query()->where('organization_id', $this->organizationId())->with('grant.fund', 'patient', 'invoice');
        $status === 'requested' ? $q->where('status', 'requested') : $q->where('status', 'applied')->whereBetween('settled_at', [$period->fromUtc(), $period->toUtc()]);
        $rows = $q->orderByDesc('id')->paginate(30)->withQueryString();

        return view('admin.welfare.discounts', ['rows' => $rows, 'period' => $period, 'status' => $status, 'total' => (clone $q)->sum('amount'), 'patients' => (clone $q)->distinct()->count('patient_id')]);
    }

    public function store(Request $request, string $assessment)
    {
        $this->allow($request, 'welfare.grant.request');
        $a = $this->find(WfAssessment::class, $assessment);
        $d = $request->validate(['fund_id' => ['required', 'integer'], 'cap_amount' => ['required', 'numeric', 'min:0.01'], 'percent' => ['nullable', 'integer', 'min:1', 'max:100'], 'purpose' => ['nullable', 'string', 'max:200']]);
        $fund = WfFund::query()->where('organization_id', $this->organizationId())->findOrFail($d['fund_id']);
        $g = $this->grants->request($a, $fund, $d['cap_amount'], isset($d['percent']) ? (int) $d['percent'] : null, $d['purpose'] ?? null, $request->user());

        return redirect()->route('admin.welfare.grants.show', $g->public_id)->with('success', __('The grant was requested.'));
    }

    public function show(Request $request, string $grant)
    {
        $this->allow($request, 'welfare.grant.view');
        $g = $this->find(WfGrant::class, $grant)->load('patient', 'fund', 'assessment', 'applications.invoice');
        $u = $request->user();
        $in = $g->status === 'approved' && $g->valid_to->gte(today());

        return view('admin.welfare.grant', ['g' => $g, 'remaining' => $this->grants->remaining($g), 'canApprove' => $u->can('welfare.grant.approve') && $g->status === 'requested' && $g->requested_by !== $u->id,
            'canApply' => $u->can('welfare.grant.apply') && $in, 'canCancel' => $u->can('welfare.grant.request') && in_array($g->status, ['requested', 'approved'], true),
            'invoices' => $in && $u->can('welfare.grant.apply') ? BillingInvoice::query()->where('organization_id', $this->organizationId())->where('patient_id', $g->patient_id)->whereIn('status', ['finalized', 'partially_paid'])->where('due_amount', '>', 0)->orderByDesc('id')->limit(20)->get(['id', 'invoice_number', 'grand_total', 'due_amount', 'invoice_date']) : collect()]);
    }

    public function approve(Request $request, string $grant)
    {
        $this->allow($request, 'welfare.grant.approve');
        $d = $request->validate(['note' => ['nullable', 'string', 'max:300']]);
        $this->grants->approve($this->find(WfGrant::class, $grant), $request->user(), $d['note'] ?? null);

        return back()->with('success', __('The grant is approved and the money is set aside.'));
    }

    public function reject(Request $request, string $grant)
    {
        $this->allow($request, 'welfare.grant.approve');
        $d = $request->validate(['note' => ['required', 'string', 'max:300']]);
        $this->grants->reject($this->find(WfGrant::class, $grant), $request->user(), $d['note']);

        return back()->with('success', __('The grant is turned down.'));
    }

    public function cancel(Request $request, string $grant)
    {
        $this->allow($request, 'welfare.grant.request');
        $d = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        $this->grants->cancel($this->find(WfGrant::class, $grant), $request->user(), $d['reason']);

        return back()->with('success', __('The grant is stopped.'));
    }

    public function apply(Request $request, string $grant)
    {
        $this->allow($request, 'welfare.grant.apply');
        $d = $request->validate(['invoice_id' => ['required', 'integer'], 'amount' => ['nullable', 'numeric', 'min:0.01']]);
        $invoice = BillingInvoice::query()->where('organization_id', $this->organizationId())->find($d['invoice_id']) ?? throw ValidationException::withMessages(['invoice_id' => __('Choose a bill.')]);
        $this->applications->apply($this->find(WfGrant::class, $grant), $invoice, $d['amount'] ?? null, $request->user());

        return back()->with('success', __('Asked Billing to take this off the bill. The fund is charged when Billing approves.'));
    }
}
