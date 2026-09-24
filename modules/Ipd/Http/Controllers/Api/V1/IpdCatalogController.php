<?php

namespace Modules\Ipd\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdRoom;
use App\Models\Ipd\IpdWard;
use App\Services\Ipd\IpdBedAvailabilityService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IpdCatalogController extends Controller
{
    public function __construct(private readonly IpdBedAvailabilityService $availability) {}

    public function wards(Request $request)
    {
        $this->authorize('viewAny', IpdWard::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $wards = IpdWard::query()
            ->forTenant($companyId, $branchId)
            ->where('is_active', true)
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($wards);
    }

    public function rooms(Request $request)
    {
        Gate::authorize('ipd.room.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $rooms = IpdRoom::query()
            ->forTenant($companyId, $branchId)
            ->where('is_active', true)
            ->when($request->filled('ward_id'), fn ($q) => $q->where('ward_id', $request->input('ward_id')))
            ->with('ward')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($rooms);
    }

    public function beds(Request $request)
    {
        $this->authorize('viewAny', IpdBed::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $beds = IpdBed::query()
            ->forTenant($companyId, $branchId)
            ->where('ipd_beds.is_active', true)
            ->with(['room.ward', 'bedType'])
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($beds);
    }

    public function bedAvailability(Request $request)
    {
        $this->authorize('viewAny', IpdBed::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $filters = $request->only(['ward_id', 'building_id', 'floor_id', 'room_id', 'bed_type_id', 'gender', 'status', 'isolation', 'search']);
        $beds = $this->availability->search($companyId, $branchId, array_filter($filters, fn ($v) => $v !== null && $v !== ''), (int) $request->input('per_page', 20));

        return ApiResponse::paginated($beds);
    }
}
