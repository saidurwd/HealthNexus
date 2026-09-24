<?php

namespace Modules\Ipd\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ipd\IpdBuilding;
use App\Models\Ipd\IpdFloor;
use App\Models\Ipd\IpdWard;
use App\Services\AuditLogger;
use App\Services\Ipd\IpdWardService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class IpdWardController extends Controller
{
    public function __construct(
        private readonly IpdWardService $wards,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', IpdWard::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $wards = IpdWard::query()
            ->forTenant($companyId, $branchId)
            ->withCount('rooms')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ipd.wards.index', compact('wards'));
    }

    public function create()
    {
        $this->authorize('create', IpdWard::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $buildings = IpdBuilding::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $floors = IpdFloor::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $companies = \App\Models\Company::all();
        $branches = \App\Models\Branch::all();

        return view('admin.ipd.wards.create', compact('buildings', 'floors', 'companies', 'branches'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', IpdWard::class);

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'building_id' => ['nullable', 'integer', 'exists:ipd_buildings,id'],
            'floor_id' => ['nullable', 'integer', 'exists:ipd_floors,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'gender_policy' => ['required', 'string', 'in:any,male,female'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'isolation_capable' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $ward = $this->wards->create($validated, $request->user());

        $this->auditLogger->log('CREATE', IpdWard::class, $ward->id, null, $ward->toArray(), $request);

        return redirect()->route('admin.ipd.wards.index')->with('success', 'Ward created.');
    }

    public function edit(IpdWard $ward)
    {
        $this->authorize('update', $ward);

        $buildings = IpdBuilding::query()->forTenant($ward->company_id)->where('is_active', true)->orderBy('name')->get();
        $floors = IpdFloor::query()->forTenant($ward->company_id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.ipd.wards.edit', compact('ward', 'buildings', 'floors'));
    }

    public function update(Request $request, IpdWard $ward)
    {
        $this->authorize('update', $ward);

        $validated = $request->validate([
            'building_id' => ['nullable', 'integer', 'exists:ipd_buildings,id'],
            'floor_id' => ['nullable', 'integer', 'exists:ipd_floors,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'gender_policy' => ['required', 'string', 'in:any,male,female'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'isolation_capable' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $oldValues = $ward->toArray();
        $this->wards->update($ward, $validated, $request->user());

        $this->auditLogger->log('UPDATE', IpdWard::class, $ward->id, $oldValues, $ward->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.wards.index')->with('success', 'Ward updated.');
    }
}
