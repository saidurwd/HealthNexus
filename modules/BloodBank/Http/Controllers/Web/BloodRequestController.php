<?php

namespace Modules\BloodBank\Http\Controllers\Web;

use App\Models\Blood\BloodIssue;
use App\Models\Blood\BloodRequest;
use App\Models\Blood\BloodUnit;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Blood\BloodCompatibilityService;
use App\Services\Blood\BloodInventoryService;
use App\Services\Blood\BloodIssueService;
use App\Services\Blood\BloodMtpService;
use App\Services\Blood\BloodPolicy;
use App\Services\Blood\BloodRequestService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BloodRequestController extends BloodController
{
    public function __construct(TenantContextResolver $tenant, private readonly BloodRequestService $requests, private readonly BloodIssueService $issues, private readonly BloodCompatibilityService $compatibility, private readonly BloodPolicy $policy, private readonly AuditLogger $audit)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'request.view');
        $requests = BloodRequest::query()->with('patient:id,public_id,first_name,last_name,enterprise_patient_no')->where('organization_id', $this->organizationId())
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByRaw("case priority when 'massive' then 0 when 'emergency' then 1 when 'urgent' then 2 else 3 end")->latest('requested_at')->paginate(25)->withQueryString();

        return view('admin.blood.requests.index', ['requests' => $requests, 'status' => $request->query('status')]);
    }

    public function create(Request $request)
    {
        $this->allow($request, 'request.create');

        return view('admin.blood.requests.create', ['components' => $this->policy->get('components'), 'priorities' => $this->policy->get('priorities')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->allow($request, 'request.create');
        $validated = $request->validate([
            'patient' => ['required', 'string', 'max:40'], 'component' => ['required', 'string'], 'units_requested' => ['required', 'integer', 'between:1,20'],
            'priority' => ['required', 'string'], 'indication' => ['nullable', 'string', 'max:300'], 'replacement_donor_offered' => ['sometimes', 'boolean'],
        ]);
        $patient = Patient::query()->where('organization_id', $this->organizationId())->where(fn ($q) => $q->where('public_id', $validated['patient'])->orWhere('enterprise_patient_no', $validated['patient']))->firstOrFail();
        $created = $this->requests->create($request->user(), $patient, $validated['component'], (int) $validated['units_requested'], $validated['priority'], 'opd', null, ['indication' => $validated['indication'] ?? null, 'replacement_donor_offered' => (bool) ($validated['replacement_donor_offered'] ?? false)]);

        return redirect()->route('admin.blood.requests.show', $created)->with('success', __('Blood request sent.'));
    }

    public function show(Request $request, BloodRequest $bloodRequest, BloodInventoryService $inventory)
    {
        $this->scoped($request, $bloodRequest, 'request.view');
        $sample = $bloodRequest->samples()->where('status', 'accepted')->where('expires_at', '>', now())->latest('id')->first();
        $group = $sample ? ['abo' => $sample->abo, 'rh' => $sample->rh] : null;
        $candidates = $inventory->fefo($this->organizationId(), $bloodRequest->component)->filter(fn (BloodUnit $u) => $group ? $this->compatibility->incompatibility($bloodRequest, $u, $group) === null : in_array($bloodRequest->priority, ['emergency', 'massive'], true) && $u->abo === 'O' && $u->rh === 'negative')->take(12);

        return view('admin.blood.requests.show', [
            'bloodRequest' => $bloodRequest->load('patient'), 'samples' => $bloodRequest->samples()->latest('id')->get(), 'reservations' => $bloodRequest->reservations()->with('unit')->latest('id')->get(),
            'issues' => $bloodRequest->issues()->with('unit')->latest('id')->get(), 'group' => $group, 'candidates' => $candidates, 'component' => $this->policy->component($bloodRequest->component),
            'rejectionReasons' => $this->policy->get('rejection_reasons'),
        ]);
    }

    public function sample(Request $request, BloodRequest $bloodRequest): RedirectResponse
    {
        $this->scoped($request, $bloodRequest, 'sample.receive');
        $validated = $request->validate([
            'patient_scan' => ['required', 'string', 'max:40'], 'abo' => ['required', 'in:'.implode(',', $this->policy->get('abo'))], 'rh' => ['required', 'in:positive,negative'],
            'collected_at' => ['required', 'date', 'before_or_equal:now'], 'labelled' => ['sometimes', 'boolean'], 'haemolysed' => ['sometimes', 'boolean'],
        ]);
        $sample = $this->requests->receiveSample($bloodRequest, $request->user(), [
            'patient_public_id' => $validated['patient_scan'], 'abo' => $validated['abo'], 'rh' => $validated['rh'], 'collected_at' => Carbon::parse($validated['collected_at']),
            'labelled' => $request->boolean('labelled'), 'haemolysed' => $request->boolean('haemolysed'), 'collected_by' => null,
        ]);

        return back()->with($sample->status === 'rejected' ? 'warning' : 'success', $sample->status === 'rejected' ? __('Sample rejected: :why', ['why' => __(Str::headline($sample->rejection_reason))]) : ($sample->status === 'provisional' ? __('First sample kept. A second, separate sample is needed to confirm the group.') : __('Sample accepted.')));
    }

    public function reserve(Request $request, BloodRequest $bloodRequest): RedirectResponse
    {
        $this->scoped($request, $bloodRequest, 'crossmatch.perform');
        $validated = $request->validate(['unit' => ['required', 'string', 'size:26'], 'method' => ['nullable', 'in:electronic,immediate_spin,full']]);
        $unit = BloodUnit::query()->where('organization_id', $bloodRequest->organization_id)->where('public_id', $validated['unit'])->firstOrFail();
        $this->compatibility->crossmatchAndReserve($bloodRequest, $unit, $request->user(), $validated['method'] ?? 'electronic');

        return back()->with('success', __('Compatible — unit reserved.'));
    }

    public function issue(Request $request, BloodRequest $bloodRequest): RedirectResponse
    {
        $this->scoped($request, $bloodRequest, 'issue.perform');
        $validated = $request->validate(['unit' => ['required', 'string', 'size:26'], 'issued_to' => ['nullable', 'string', 'max:100'], 'issue_temp_c' => ['nullable', 'numeric', 'between:-5,40']]);
        $unit = BloodUnit::query()->where('organization_id', $bloodRequest->organization_id)->where('public_id', $validated['unit'])->firstOrFail();
        $issue = $this->issues->issue($bloodRequest, $unit, $request->user(), $validated['issued_to'] ?? null, isset($validated['issue_temp_c']) ? (float) $validated['issue_temp_c'] : null);
        $this->audit->log('BLOOD_ISSUE', BloodIssue::class, $issue->id, null, ['unit' => $unit->unit_number, 'request' => $bloodRequest->request_number], $request);

        return back()->with('success', __('Unit issued.'));
    }

    public function emergency(Request $request, BloodRequest $bloodRequest): RedirectResponse
    {
        $this->scoped($request, $bloodRequest, 'issue.emergency');
        $validated = $request->validate(['unit' => ['required', 'string', 'size:26'], 'authorised_by' => ['required', 'string', 'max:100'], 'reason' => ['required', 'string', 'max:300'], 'issued_to' => ['nullable', 'string', 'max:100']]);
        $unit = BloodUnit::query()->where('organization_id', $bloodRequest->organization_id)->where('public_id', $validated['unit'])->firstOrFail();
        $issue = $this->issues->emergencyIssue($bloodRequest, $unit, $request->user(), $validated['authorised_by'], $validated['reason'], $validated['issued_to'] ?? null);
        $this->audit->log('BLOOD_EMERGENCY_ISSUE', BloodIssue::class, $issue->id, null, ['unit' => $unit->unit_number, 'authorised_by' => $validated['authorised_by']], $request);

        return back()->with('warning', __('Emergency release recorded. A cross-match is still owed.'));
    }

    public function retrospective(Request $request, BloodIssue $issue): RedirectResponse
    {
        $this->scoped($request, $issue, 'crossmatch.perform');
        $match = $this->issues->retrospectiveCrossmatch($issue, $request->user());

        return back()->with($match->result === 'compatible' ? 'success' : 'warning', $match->result === 'compatible' ? __('Retrospective cross-match is compatible.') : __('INCOMPATIBLE — an incident has been opened.'));
    }

    public function returnUnit(Request $request, BloodIssue $issue): RedirectResponse
    {
        $this->scoped($request, $issue, 'return.accept');
        $validated = $request->validate(['minutes_out' => ['required', 'integer', 'between:0,1440'], 'return_temp_c' => ['required', 'numeric', 'between:-30,45'], 'reason' => ['nullable', 'string', 'max:200']]);
        $done = $this->issues->returnUnit($issue, $request->user(), (int) $validated['minutes_out'], (float) $validated['return_temp_c'], $validated['reason'] ?? null);

        return back()->with($done->return_accepted ? 'success' : 'warning', $done->return_accepted ? __('Returned to the shelf.') : __('Outside the return rule — the unit was discarded.'));
    }

    public function cancel(Request $request, BloodRequest $bloodRequest): RedirectResponse
    {
        $this->scoped($request, $bloodRequest, 'request.cancel');
        $validated = $request->validate(['reason' => ['required', 'string', 'max:200']]);
        $this->requests->cancel($bloodRequest, $request->user(), $validated['reason']);

        return back()->with('success', __('Request cancelled.'));
    }

    public function mtp(Request $request, BloodMtpService $mtp): RedirectResponse
    {
        $this->allow($request, 'mtp.activate');
        $validated = $request->validate(['patient' => ['required', 'string', 'max:40'], 'authorised_by' => ['required', 'string', 'max:100'], 'reason' => ['required', 'string', 'max:300']]);
        $patient = Patient::query()->where('organization_id', $this->organizationId())->where(fn ($q) => $q->where('public_id', $validated['patient'])->orWhere('enterprise_patient_no', $validated['patient']))->firstOrFail();
        $result = $mtp->activate($request->user(), $patient, $validated['authorised_by'], $validated['reason']);
        $this->audit->log('BLOOD_MTP', Patient::class, $patient->id, null, ['issued' => $result['issued'], 'short' => $result['short']], $request);

        return redirect()->route('admin.blood.requests.index')->with($result['short'] ? 'warning' : 'success', __('Massive transfusion protocol: :n unit(s) released.', ['n' => $result['issued']]).($result['short'] ? ' '.__('Short: :list', ['list' => collect($result['short'])->map(fn ($n, $c) => "$c $n")->implode(', ')]) : ''));
    }
}
