<?php

namespace Modules\Radiology\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Radiology\RadiologyExamination;
use App\Services\AuditLogger;
use App\Services\Radiology\RadiologyExaminationService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class RadiologyExaminationController extends Controller
{
    public function __construct(
        private readonly RadiologyExaminationService $examinations,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyExamination::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $examinations = RadiologyExamination::query()
            ->forTenant($companyId, $branchId)
            ->whereIn('status', ['scheduled', 'checked_in', 'preparing', 'ready', 'in_progress'])
            ->with(['orderItem.radiologyOrder.patient', 'orderItem.procedure', 'modality'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('scheduled_at')
            ->paginate(20);

        return view('admin.radiology.examinations.index', compact('examinations'));
    }

    public function show(RadiologyExamination $examination)
    {
        $this->authorize('view', $examination);

        $examination->load(['orderItem.radiologyOrder.patient', 'orderItem.procedure', 'modality', 'technologist', 'studies']);

        return view('admin.radiology.examinations.show', compact('examination'));
    }

    public function checkIn(Request $request, RadiologyExamination $examination)
    {
        $this->authorize('start', $examination);

        $oldValues = $examination->toArray();
        $this->examinations->checkIn($examination, $request->user());

        $this->auditLogger->log('CHECK_IN', RadiologyExamination::class, $examination->id, $oldValues, $examination->fresh()->toArray(), $request);

        return redirect()->route('admin.radiology.examinations.show', $examination)->with('success', 'Patient checked in.');
    }

    public function startPreparation(Request $request, RadiologyExamination $examination)
    {
        $this->authorize('start', $examination);

        $oldValues = $examination->toArray();
        app(\App\Services\Radiology\RadiologyExaminationService::class)->startPreparation($examination);

        $this->auditLogger->log('START_PREPARATION', RadiologyExamination::class, $examination->id, $oldValues, $examination->fresh()->toArray(), $request);

        return redirect()->route('admin.radiology.examinations.show', $examination)->with('success', 'Preparation started.');
    }

    public function start(Request $request, RadiologyExamination $examination)
    {
        $this->authorize('start', $examination);

        $oldValues = $examination->toArray();
        $this->examinations->start($examination, $request->user());

        $this->auditLogger->log('START', RadiologyExamination::class, $examination->id, $oldValues, $examination->fresh()->toArray(), $request);

        return redirect()->route('admin.radiology.examinations.show', $examination)->with('success', 'Examination started.');
    }

    public function complete(Request $request, RadiologyExamination $examination)
    {
        $this->authorize('complete', $examination);

        $validated = $request->validate(['technical_notes' => ['nullable', 'string']]);
        $oldValues = $examination->toArray();

        $this->examinations->complete($examination, $request->user(), $validated);

        $this->auditLogger->log('COMPLETE', RadiologyExamination::class, $examination->id, $oldValues, $examination->fresh()->toArray(), $request);

        return redirect()->route('admin.radiology.examinations.show', $examination)->with('success', 'Examination completed — checking PACS for images.');
    }
}
