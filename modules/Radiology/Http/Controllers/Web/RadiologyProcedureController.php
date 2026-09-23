<?php

namespace Modules\Radiology\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Radiology\RadiologyBodyPart;
use App\Models\Radiology\RadiologyProcedure;
use App\Models\Radiology\RadiologySection;
use App\Services\AuditLogger;
use App\Services\Radiology\RadiologyProcedureService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Radiology\Http\Requests\Web\StoreRadiologyProcedureRequest;
use Modules\Radiology\Http\Requests\Web\UpdateRadiologyProcedureRequest;

class RadiologyProcedureController extends Controller
{
    public function __construct(
        private readonly RadiologyProcedureService $procedures,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyProcedure::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $procedures = RadiologyProcedure::query()
            ->forTenant($companyId, $branchId)
            ->with(['section', 'bodyPart'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', '%'.$request->input('search').'%')
                ->orWhere('code', 'like', '%'.$request->input('search').'%')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.radiology.procedures.index', compact('procedures'));
    }

    public function create()
    {
        $this->authorize('create', RadiologyProcedure::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $sections = RadiologySection::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $bodyParts = RadiologyBodyPart::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $companies = Company::all();
        $branches = Branch::all();

        return view('admin.radiology.procedures.create', compact('sections', 'bodyParts', 'companies', 'branches'));
    }

    public function store(StoreRadiologyProcedureRequest $request)
    {
        $this->authorize('create', RadiologyProcedure::class);

        $procedure = $this->procedures->create($request->validated(), $request->user());

        $this->auditLogger->log('CREATE', RadiologyProcedure::class, $procedure->id, null, $procedure->toArray(), $request);

        return redirect()->route('admin.radiology.procedures.index')->with('success', 'Radiology procedure created.');
    }

    public function edit(RadiologyProcedure $procedure)
    {
        $this->authorize('update', $procedure);

        $sections = RadiologySection::query()->forTenant($procedure->company_id)->where('is_active', true)->orderBy('name')->get();
        $bodyParts = RadiologyBodyPart::query()->forTenant($procedure->company_id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.radiology.procedures.edit', compact('procedure', 'sections', 'bodyParts'));
    }

    public function update(UpdateRadiologyProcedureRequest $request, RadiologyProcedure $procedure)
    {
        $this->authorize('update', $procedure);

        $oldValues = $procedure->toArray();
        $this->procedures->update($procedure, $request->validated(), $request->user());

        $this->auditLogger->log('UPDATE', RadiologyProcedure::class, $procedure->id, $oldValues, $procedure->fresh()->toArray(), $request);

        return redirect()->route('admin.radiology.procedures.index')->with('success', 'Radiology procedure updated.');
    }

    public function destroy(Request $request, RadiologyProcedure $procedure)
    {
        $this->authorize('delete', $procedure);

        $oldValues = $procedure->toArray();
        $this->procedures->deactivate($procedure, $request->user());

        $this->auditLogger->log('DEACTIVATE', RadiologyProcedure::class, $procedure->id, $oldValues, null, $request);

        return redirect()->route('admin.radiology.procedures.index')->with('success', 'Radiology procedure deactivated.');
    }
}
