<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingEscalation;
use App\Services\AuditLogger;
use App\Services\Nursing\NursingEscalationService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NursingEscalationController extends Controller
{
    public function __construct(
        private readonly NursingEscalationService $escalations,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', NursingEscalation::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $escalations = NursingEscalation::forTenant($companyId, $branchId)
            ->with(['patient', 'admission', 'createdBy'])
            ->when($request->boolean('open'), fn ($q) => $q->whereNull('resolved_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.nursing.escalations.index', compact('escalations'));
    }

    public function store(Request $request, NursingEpisode $episode)
    {
        $this->authorize('create', NursingEscalation::class);

        $validated = $request->validate([
            'concern' => ['required', 'string'],
            'recipient_type' => ['required', 'string', 'max:255'],
            'recipient_id' => ['nullable', 'integer'],
            'severity' => ['nullable', 'string', 'in:informational,low,moderate,high,critical'],
        ]);

        $escalation = $this->escalations->create($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', NursingEscalation::class, $escalation->id, null, $escalation->toArray(), $request);

        return back()->with('success', 'Escalation raised.');
    }

    public function acknowledge(Request $request, NursingEscalation $escalation)
    {
        $this->authorize('acknowledge', $escalation);

        try {
            $this->escalations->acknowledge($escalation, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('ACKNOWLEDGE', NursingEscalation::class, $escalation->id, null, ['acknowledged_at' => now()], $request);

        return back()->with('success', 'Escalation acknowledged.');
    }

    public function resolve(Request $request, NursingEscalation $escalation)
    {
        $this->authorize('resolve', $escalation);

        $validated = $request->validate(['action_taken' => ['required', 'string']]);

        try {
            $this->escalations->resolve($escalation, $request->user(), $validated['action_taken']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('RESOLVE', NursingEscalation::class, $escalation->id, null, $validated, $request);

        return back()->with('success', 'Escalation resolved.');
    }
}
