<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingHandover;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Nursing\NursingHandoverService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NursingHandoverController extends Controller
{
    public function __construct(
        private readonly NursingHandoverService $handovers,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', NursingHandover::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $handovers = NursingHandover::forTenant($companyId, $branchId)
            ->with(['episode.patient', 'outgoingNurse', 'incomingNurse'])
            ->when($request->boolean('pending'), fn ($q) => $q->where('status', NursingHandover::STATUS_PENDING_ACKNOWLEDGEMENT))
            ->latest('prepared_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.nursing.handover.index', compact('handovers'));
    }

    public function show(NursingHandover $handover)
    {
        $this->authorize('view', $handover);

        $handover->load(['items', 'episode.patient', 'outgoingNurse', 'incomingNurse']);

        return view('admin.nursing.handover.show', compact('handover'));
    }

    public function store(Request $request, NursingEpisode $episode)
    {
        $this->authorize('create', NursingHandover::class);

        $handover = $this->handovers->prepare($episode, $request->user(), $request->input('shift_id'));

        $this->auditLogger->log('CREATE', NursingHandover::class, $handover->id, null, $handover->toArray(), $request);

        return redirect()->route('admin.nursing.handover.show', $handover)->with('success', 'Handover prepared.');
    }

    public function finalize(Request $request, NursingHandover $handover)
    {
        $this->authorize('create', NursingHandover::class);

        $validated = $request->validate(['incoming_nurse_id' => ['nullable', 'integer', 'exists:users,id']]);
        $incomingNurse = ! empty($validated['incoming_nurse_id']) ? User::find($validated['incoming_nurse_id']) : null;

        $this->handovers->finalize($handover, $incomingNurse);

        $this->auditLogger->log('FINALIZE', NursingHandover::class, $handover->id, null, ['status' => 'pending_acknowledgement'], $request);

        return back()->with('success', 'Handover finalized and sent for acknowledgement.');
    }

    public function acknowledge(Request $request, NursingHandover $handover)
    {
        $this->authorize('acknowledge', $handover);

        try {
            $this->handovers->acknowledge($handover, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('ACKNOWLEDGE', NursingHandover::class, $handover->id, null, ['status' => 'acknowledged'], $request);

        return back()->with('success', 'Handover acknowledged.');
    }
}
