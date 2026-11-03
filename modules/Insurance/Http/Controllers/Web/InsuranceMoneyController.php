<?php

namespace Modules\Insurance\Http\Controllers\Web;

use App\Models\Billing\BillingCorporate;
use App\Models\Insurance\CorporateBatch;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsurancePayer;
use App\Models\Insurance\InsuranceRemittance;
use App\Models\Insurance\InsuranceWriteoff;
use App\Services\AuditLogger;
use App\Services\Insurance\CorporateCreditService;
use App\Services\Insurance\InsuranceRemittanceService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Money in: remittances from payers, write-offs, and corporate credit statements. */
class InsuranceMoneyController extends InsuranceController
{
    public function __construct(TenantContextResolver $tenant, private readonly InsuranceRemittanceService $remittances, private readonly CorporateCreditService $corporate, private readonly AuditLogger $audit)
    {
        parent::__construct($tenant);
    }

    public function remittances(Request $request)
    {
        $this->allow($request, 'remittance.view');
        $org = $this->organizationId();

        return view('admin.insurance.remittances.index', [
            'remittances' => InsuranceRemittance::query()->with('payer:id,name')->where('organization_id', $org)->latest('id')->paginate(20), 'payers' => InsurancePayer::where('organization_id', $org)->orderBy('name')->get(),
            'writeoffs' => InsuranceWriteoff::query()->where('organization_id', $org)->where('status', 'requested')->latest('id')->get(), 'claimNumbers' => InsuranceClaim::where('organization_id', $org)->pluck('claim_number', 'id'),
        ]);
    }

    public function storeRemittance(Request $request): RedirectResponse
    {
        $this->allow($request, 'remittance.record');
        $validated = $request->validate(['payer' => ['required', 'string', 'size:26'], 'amount' => ['required', 'numeric', 'min:0.01'], 'received_on' => ['required', 'date', 'before_or_equal:today'], 'payer_reference' => ['nullable', 'string', 'max:80']]);
        $payer = InsurancePayer::query()->where('organization_id', $this->organizationId())->where('public_id', $validated['payer'])->firstOrFail();
        $remittance = $this->remittances->record($payer, number_format((float) $validated['amount'], 2, '.', ''), $validated['received_on'], $request->user(), $validated['payer_reference'] ?? null);

        return redirect()->route('admin.insurance.remittances.show', $remittance)->with('success', __('Payment received and recorded. Now match it to claims.'));
    }

    public function showRemittance(Request $request, InsuranceRemittance $remittance)
    {
        $this->scoped($request, $remittance, 'remittance.view');

        return view('admin.insurance.remittances.show', [
            'remittance' => $remittance->load('payer'), 'lines' => $remittance->lines()->get(), 'claimNumbers' => InsuranceClaim::where('organization_id', $remittance->organization_id)->pluck('claim_number', 'id'),
            'open' => InsuranceClaim::query()->with('patient:id,first_name,last_name')->where('organization_id', $remittance->organization_id)->where('payer_id', $remittance->payer_id)->whereIn('status', ['submitted', 'acknowledged', 'accepted', 'appealed', 'partially_paid'])->orderBy('submitted_at')->get(),
        ]);
    }

    public function allocate(Request $request, InsuranceRemittance $remittance): RedirectResponse
    {
        $this->scoped($request, $remittance, 'remittance.record');
        $validated = $request->validate(['claim' => ['required', 'string', 'size:26'], 'paid' => ['required', 'numeric', 'min:0'], 'adjustment' => ['nullable', 'numeric', 'min:0'], 'adjustment_reason' => ['nullable', 'string', 'max:200']]);
        $claim = InsuranceClaim::query()->where('organization_id', $remittance->organization_id)->where('public_id', $validated['claim'])->firstOrFail();
        $this->remittances->allocate($remittance, $claim, number_format((float) $validated['paid'], 2, '.', ''), $request->user(), number_format((float) ($validated['adjustment'] ?? 0), 2, '.', ''), $validated['adjustment_reason'] ?? null);
        $this->audit->log('INSURANCE_PAYMENT_ALLOCATED', InsuranceClaim::class, $claim->id, null, ['paid' => $validated['paid'], 'remittance' => $remittance->remittance_number], $request);

        return back()->with('success', __('Matched to the claim and posted on the invoice.'));
    }

    public function requestWriteoff(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        $this->scoped($request, $claim, 'writeoff.request');
        $validated = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'reason' => ['required', 'string', 'max:300']]);
        $this->remittances->requestWriteoff($claim, number_format((float) $validated['amount'], 2, '.', ''), $validated['reason'], $request->user());

        return back()->with('success', __('Write-off sent for approval.'));
    }

    public function decideWriteoff(Request $request, InsuranceWriteoff $writeoff): RedirectResponse
    {
        $this->scoped($request, $writeoff, 'writeoff.approve');
        $validated = $request->validate(['decision' => ['required', 'in:approve,reject']]);
        $this->remittances->decideWriteoff($writeoff, $validated['decision'] === 'approve', $request->user());
        $this->audit->log('INSURANCE_WRITEOFF', InsuranceWriteoff::class, $writeoff->id, null, ['decision' => $validated['decision'], 'amount' => (string) $writeoff->amount], $request);

        return back()->with('success', __('Decision recorded.'));
    }

    public function corporates(Request $request)
    {
        $this->allow($request, 'corporate.view');
        $org = $this->organizationId();
        $clients = BillingCorporate::query()->where('organization_id', $org)->orderBy('name')->get()->map(fn ($c) => ['client' => $c, 'position' => $this->corporate->position($c)]);

        return view('admin.insurance.corporate', ['clients' => $clients, 'batches' => CorporateBatch::query()->where('organization_id', $org)->latest('id')->limit(30)->get(), 'names' => BillingCorporate::where('organization_id', $org)->pluck('name', 'id')]);
    }

    public function createBatch(Request $request): RedirectResponse
    {
        $this->allow($request, 'corporate.manage');
        $validated = $request->validate(['corporate' => ['required', 'integer'], 'period_from' => ['required', 'date'], 'period_to' => ['required', 'date', 'after_or_equal:period_from']]);
        $client = BillingCorporate::query()->where('organization_id', $this->organizationId())->findOrFail($validated['corporate']);
        $this->corporate->createBatch($client, $validated['period_from'], $validated['period_to'], $request->user());

        return back()->with('success', __('Statement made.'));
    }

    public function issueBatch(Request $request, CorporateBatch $batch): RedirectResponse
    {
        $this->scoped($request, $batch, 'corporate.manage');
        $this->corporate->issue($batch, $request->user());

        return back()->with('success', __('Statement issued.'));
    }

    public function payBatch(Request $request, CorporateBatch $batch): RedirectResponse
    {
        $this->scoped($request, $batch, 'corporate.manage');
        $validated = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'reference' => ['nullable', 'string', 'max:80']]);
        $this->corporate->receivePayment($batch, number_format((float) $validated['amount'], 2, '.', ''), $request->user(), $validated['reference'] ?? null);

        return back()->with('success', __('Payment recorded.'));
    }
}
