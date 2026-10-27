<?php

namespace Modules\Insurance\Http\Controllers\Web;

use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Billing\BillingInvoice;
use App\Models\Insurance\InsuranceAppeal;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsuranceClaimDocument;
use App\Models\Insurance\InsuranceClaimQuery;
use App\Models\Insurance\InsurancePayer;
use App\Models\Insurance\InsuranceSubmission;
use App\Services\AuditLogger;
use App\Services\Insurance\InsuranceClaimService;
use App\Services\Insurance\InsuranceQueryService;
use App\Services\Insurance\InsuranceSubmissionService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InsuranceClaimController extends InsuranceController
{
    public function __construct(TenantContextResolver $tenant, private readonly InsuranceClaimService $claims, private readonly InsuranceSubmissionService $submissions, private readonly InsuranceQueryService $queries, private readonly AuditLogger $audit)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'claim.view');
        $org = $this->organizationId();
        $claims = InsuranceClaim::query()->with(['payer:id,name', 'patient:id,first_name,last_name,enterprise_patient_no'])->where('organization_id', $org)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))->when($request->query('payer'), fn ($q, $v) => $q->where('payer_id', InsurancePayer::where('organization_id', $org)->where('public_id', $v)->value('id')))
            ->when($request->query('q'), fn ($q, $v) => $q->where('claim_number', 'like', "%$v%"))->latest('id')->paginate(25)->withQueryString();

        return view('admin.insurance.claims.index', ['claims' => $claims, 'payers' => InsurancePayer::where('organization_id', $org)->orderBy('name')->get(), 'statuses' => array_keys(config('insurance.claim_transitions')), 'filters' => $request->query(),
            'submissions' => InsuranceSubmission::where('organization_id', $org)->latest('id')->limit(8)->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->allow($request, 'claim.create');
        $validated = $request->validate(['invoice' => ['required', 'string', 'max:60']]);
        $invoice = BillingInvoice::query()->where('organization_id', $this->organizationId())->where('invoice_number', $validated['invoice'])->firstOrFail();
        $claim = $this->claims->generate($invoice, $request->user());

        return redirect()->route('admin.insurance.claims.show', $claim)->with('success', __('Claim drafted from the invoice.'));
    }

    public function show(Request $request, InsuranceClaim $claim)
    {
        $this->scoped($request, $claim, 'claim.view');

        return view('admin.insurance.claims.show', [
            'claim' => $claim->load(['payer', 'patient']), 'lines' => $claim->lines()->orderBy('id')->get(), 'events' => $claim->events()->get(), 'documents' => $claim->documents()->get(), 'queries' => $claim->queries()->latest('id')->get(),
            'appeals' => InsuranceAppeal::where('claim_id', $claim->id)->latest('id')->get(), 'reasons' => config('insurance.denial_reasons'), 'next' => config("insurance.claim_transitions.{$claim->status}", []),
            'versions' => InsuranceClaim::where('invoice_id', $claim->invoice_id)->where('payer_id', $claim->payer_id)->orderBy('version')->get(['id', 'public_id', 'claim_number', 'version', 'status']),
        ]);
    }

    public function ready(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        $this->scoped($request, $claim, 'claim.edit');
        $errors = $this->claims->markReady($claim, $request->user());

        return back()->with($errors ? 'warning' : 'success', $errors ? __(':n problem(s) must be fixed before this claim can go.', ['n' => count($errors)]) : __('Claim is ready to submit.'));
    }

    public function rebuild(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        $this->scoped($request, $claim, 'claim.edit');
        abort_unless($claim->status === 'draft', 422);
        $invoice = BillingInvoice::findOrFail($claim->invoice_id);
        $new = $this->claims->generate($invoice, $request->user(), $claim->policy_id ? BillingInsurancePolicy::find($claim->policy_id) : null, $claim);
        $this->claims->transition($claim, 'cancelled', $request->user(), "Rebuilt as {$new->claim_number}");

        return redirect()->route('admin.insurance.claims.show', $new)->with('success', __('Claim rebuilt from the invoice.'));
    }

    public function document(Request $request, InsuranceClaim $claim, InsuranceClaimDocument $document): RedirectResponse
    {
        $this->scoped($request, $claim, 'claim.edit');
        abort_unless($document->claim_id === $claim->id && ! $claim->isLocked(), 404);
        $document->update(['received' => ! $document->received]);

        return back();
    }

    public function newVersion(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        $this->scoped($request, $claim, 'claim.create');
        $new = $this->claims->newVersion($claim, $request->user());

        return redirect()->route('admin.insurance.claims.show', $new)->with('success', __('New version made.'));
    }

    public function secondary(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        $this->scoped($request, $claim, 'claim.create');
        $new = $this->claims->generateSecondary($claim, $request->user());

        return redirect()->route('admin.insurance.claims.show', $new)->with('success', __('Claim for the second policy drafted.'));
    }

    public function cancel(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        $this->scoped($request, $claim, 'claim.cancel');
        $this->claims->transition($claim, 'cancelled', $request->user(), $request->input('reason'));

        return back()->with('success', __('Claim cancelled.'));
    }

    public function export(Request $request): RedirectResponse
    {
        $this->allow($request, 'claim.submit');
        $validated = $request->validate(['payer' => ['required', 'string', 'size:26'], 'claims' => ['required', 'array', 'min:1'], 'claims.*' => ['string', 'size:26']]);
        $payer = InsurancePayer::query()->where('organization_id', $this->organizationId())->where('public_id', $validated['payer'])->firstOrFail();
        $claims = InsuranceClaim::query()->where('organization_id', $payer->organization_id)->whereIn('public_id', $validated['claims'])->get();
        $batch = $this->submissions->exportBatch($payer, $claims, $request->user());
        $this->audit->log('INSURANCE_BATCH_EXPORT', InsuranceSubmission::class, $batch->id, null, ['batch' => $batch->batch_number, 'claims' => $batch->claim_count], $request);

        return back()->with('success', __('Batch :n created. Send the file, then mark it submitted.', ['n' => $batch->batch_number]));
    }

    public function download(Request $request, InsuranceSubmission $submission)
    {
        $this->scoped($request, $submission, 'claim.submit');
        abort_unless($submission->file_path && Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->download($submission->file_path, $submission->batch_number.'.csv');
    }

    public function markSubmitted(Request $request, InsuranceSubmission $submission): RedirectResponse
    {
        $this->scoped($request, $submission, 'claim.submit');
        $validated = $request->validate(['payer_reference' => ['required', 'string', 'max:80']]);
        $this->submissions->markSubmitted($submission, $validated['payer_reference'], $request->user());

        return back()->with('success', __('Batch marked as submitted.'));
    }

    public function raise(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        $this->scoped($request, $claim, 'query.manage');
        $validated = $request->validate(['kind' => ['required', 'in:query,rejection,denial'], 'reason_code' => ['nullable', 'string', 'max:10'], 'text' => ['required', 'string', 'max:500']]);
        $this->queries->raise($claim, $validated['kind'], $validated['reason_code'] ?? null, $validated['text'], $request->user());

        return back()->with('warning', __('Payer reply recorded.'));
    }

    public function answer(Request $request, InsuranceClaimQuery $query): RedirectResponse
    {
        $this->scoped($request, $query, 'query.manage');
        $validated = $request->validate(['response' => ['required', 'string', 'max:1000']]);
        $this->queries->answer($query, $validated['response'], $request->user());

        return back()->with('success', __('Answer sent with the claim.'));
    }

    public function appeal(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        $this->scoped($request, $claim, 'appeal.manage');
        $validated = $request->validate(['grounds' => ['required', 'string', 'max:1000']]);
        $this->queries->appeal($claim, $validated['grounds'], $request->user(), $claim->queries()->where('kind', 'denial')->latest('id')->first());

        return back()->with('success', __('Appeal filed.'));
    }

    public function decideAppeal(Request $request, InsuranceAppeal $appeal): RedirectResponse
    {
        $this->scoped($request, $appeal, 'appeal.manage');
        $validated = $request->validate(['outcome' => ['required', 'in:overturned,upheld'], 'note' => ['nullable', 'string', 'max:500']]);
        $this->queries->decideAppeal($appeal, $validated['outcome'] === 'overturned', $validated['note'] ?? null, $request->user());

        return back()->with('success', __('Appeal decision recorded.'));
    }
}
