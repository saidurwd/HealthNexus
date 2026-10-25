<?php

namespace Modules\Icu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Icu\IcuAdmissionRequest;
use App\Models\Icu\IcuEpisode;
use App\Services\AuditLogger;
use App\Services\Icu\IcuEpisodeService;
use App\Services\Icu\IcuTransferService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IcuEpisodeController extends Controller
{
    public function __construct(
        private readonly IcuEpisodeService $episodes,
        private readonly IcuTransferService $transfers,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', IcuEpisode::class);

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $episodes = IcuEpisode::forTenant($organizationId, $branchId)
            ->with(['patient', 'icuBed', 'icuUnit'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('admitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.icu.episodes.index', compact('episodes'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', IcuEpisode::class);

        $icuRequest = $request->filled('admission_request_id')
            ? IcuAdmissionRequest::where('status', IcuAdmissionRequest::STATUS_ACCEPTED)->findOrFail($request->input('admission_request_id'))
            : null;

        return view('admin.icu.episodes.create', ['icuRequest' => $icuRequest]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', IcuEpisode::class);

        $validated = $request->validate([
            'admission_request_id' => ['required', 'integer', 'exists:icu_admission_requests,id'],
            'bed_id' => ['required', 'integer', 'exists:ipd_beds,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'attending_provider_id' => ['nullable', 'integer', 'exists:providers,id'],
        ]);

        $icuRequest = IcuAdmissionRequest::findOrFail($validated['admission_request_id']);

        try {
            $episode = $this->episodes->admit($icuRequest, $validated, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $this->auditLogger->log('CREATE', IcuEpisode::class, $episode->id, null, $episode->toArray(), $request);

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', 'Patient admitted to ICU.');
    }

    public function show(IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        $episode->load(['patient', 'icuBed', 'icuUnit', 'admittingProvider', 'attendingProvider', 'events']);

        return view('admin.icu.episodes.show', compact('episode'));
    }

    public function transfer(Request $request, IcuEpisode $episode)
    {
        $this->authorize('transfer', $episode);

        $validated = $request->validate([
            'destination' => ['required', 'string'],
            'reason' => ['nullable', 'string'],
        ]);

        try {
            $episode = $this->transfers->completeTransfer($episode, $validated['destination'], $request->user(), $validated['reason'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', 'ICU episode transferred.');
    }
}
