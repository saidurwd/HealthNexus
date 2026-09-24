<?php

namespace Modules\Ipd\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ipd\IpdWard;
use App\Services\Ipd\IpdBedAvailabilityService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IpdBedBoardController extends Controller
{
    public function __construct(private readonly IpdBedAvailabilityService $availability) {}

    public function index(Request $request)
    {
        Gate::authorize('ipd.bed.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $filters = $request->only(['ward_id', 'building_id', 'floor_id', 'bed_type_id', 'gender', 'status', 'isolation']);
        $beds = $this->availability->search($companyId, $branchId, array_filter($filters, fn ($v) => $v !== null && $v !== ''), 200);

        $wardsGrouped = $beds->getCollection()
            ->groupBy(fn ($bed) => $bed->room->ward->name ?? 'Unassigned')
            ->map(fn ($bedsInWard) => $bedsInWard->groupBy(fn ($bed) => $bed->room->room_number ?? '—'));

        $wards = IpdWard::query()->forTenant($companyId, $branchId)->where('is_active', true)->orderBy('name')->get();

        return view('admin.ipd.bed-board.index', compact('wardsGrouped', 'wards'));
    }

    public function availability(Request $request)
    {
        Gate::authorize('ipd.bed.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $filters = $request->only(['ward_id', 'building_id', 'floor_id', 'room_id', 'bed_type_id', 'gender', 'status', 'isolation', 'search']);
        $beds = $this->availability->search($companyId, $branchId, array_filter($filters, fn ($v) => $v !== null && $v !== ''));

        return view('admin.ipd.bed-availability.index', compact('beds'));
    }
}
