<?php

namespace Modules\Radiology\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\Radiology\RadiologyModality;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyOrderItem;
use App\Services\AuditLogger;
use App\Services\Radiology\RadiologySchedulingService;
use App\Services\TenantContextResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Radiology\Http\Requests\Web\ScheduleExaminationRequest;

class RadiologySchedulingController extends Controller
{
    public function __construct(
        private readonly RadiologySchedulingService $scheduling,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function create(RadiologyOrderItem $item)
    {
        $this->authorize('view', $item->radiologyOrder);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $modalities = RadiologyModality::query()->forTenant($companyId, $branchId)->where('is_active', true)->get();
        $technologists = Provider::query()
            ->where('company_id', $companyId)
            ->whereIn('provider_type', ['radiologist', 'radiology_technician'])
            ->where('status', 'active')
            ->get();

        return view('admin.radiology.scheduling.create', compact('item', 'modalities', 'technologists'));
    }

    public function store(ScheduleExaminationRequest $request, RadiologyOrderItem $item)
    {
        $this->authorize('view', $item->radiologyOrder);

        $modality = RadiologyModality::findOrFail($request->validated('modality_id'));
        $technologist = Provider::findOrFail($request->validated('technologist_id'));

        $examination = $this->scheduling->schedule(
            $item,
            $modality,
            $technologist,
            Carbon::parse($request->validated('scheduled_at')),
            $request->user(),
            (int) ($request->validated('duration_minutes') ?? $item->procedure?->duration_minutes ?? 30),
        );

        $this->auditLogger->log('SCHEDULE', \App\Models\Radiology\RadiologyExamination::class, $examination->id, null, $examination->toArray(), $request);

        return redirect()->route('admin.radiology.orders.show', $item->radiologyOrder)->with('success', 'Examination scheduled.');
    }
}
