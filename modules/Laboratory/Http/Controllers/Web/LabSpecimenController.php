<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabSpecimen;
use App\Services\AuditLogger;
use App\Services\Laboratory\SpecimenService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Laboratory\Http\Requests\Web\CollectSpecimenRequest;
use Modules\Laboratory\Http\Requests\Web\RejectSpecimenRequest;

class LabSpecimenController extends Controller
{
    public function __construct(
        private readonly SpecimenService $specimens,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', LabSpecimen::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $specimens = LabSpecimen::query()
            ->forTenant($companyId, $branchId)
            ->with(['labOrder.patient', 'specimenType'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('accession_number', 'like', '%'.$request->input('search').'%'))
            ->latest('collected_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.lab.specimens.index', compact('specimens'));
    }

    public function show(LabSpecimen $specimen)
    {
        $this->authorize('view', $specimen);

        $specimen->load(['labOrder.patient', 'specimenType', 'containerType', 'orderItems.test', 'collectedBy', 'receivedBy']);

        return view('admin.lab.specimens.show', compact('specimen'));
    }

    public function collect(CollectSpecimenRequest $request, LabOrder $order)
    {
        $this->authorize('collect', LabSpecimen::class);

        $specimen = $this->specimens->collect($order, $request->validated(), $request->user());

        $this->auditLogger->log('COLLECT', LabSpecimen::class, $specimen->id, null, $specimen->toArray(), $request);

        return redirect()->route('admin.lab.specimens.show', $specimen)->with('success', 'Specimen collected: '.$specimen->accession_number);
    }

    public function receive(Request $request, LabSpecimen $specimen)
    {
        $this->authorize('receive', $specimen);

        $oldValues = $specimen->toArray();
        $this->specimens->receive($specimen, $request->user());

        $this->auditLogger->log('RECEIVE', LabSpecimen::class, $specimen->id, $oldValues, $specimen->fresh()->toArray(), $request);

        return redirect()->route('admin.lab.specimens.show', $specimen)->with('success', 'Specimen received.');
    }

    public function reject(RejectSpecimenRequest $request, LabSpecimen $specimen)
    {
        $this->authorize('reject', $specimen);

        $oldValues = $specimen->toArray();
        $this->specimens->reject($specimen, $request->validated('reason'), $request->user(), $request->validated('notes'));

        $this->auditLogger->log('REJECT', LabSpecimen::class, $specimen->id, $oldValues, $specimen->fresh()->toArray(), $request);

        return redirect()->route('admin.lab.specimens.show', $specimen)->with('success', 'Specimen rejected — recollection required.');
    }
}
