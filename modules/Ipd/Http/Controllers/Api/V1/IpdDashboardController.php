<?php

namespace Modules\Ipd\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdAdmissionRequest;
use App\Models\Ipd\IpdBed;
use Illuminate\Support\Facades\Gate;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class IpdDashboardController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('ipd.dashboard.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();
        $today = now()->toDateString();

        $bedCounts = IpdBed::query()
            ->forTenant($companyId, $branchId)
            ->where('ipd_beds.is_active', true)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalBeds = (int) $bedCounts->sum();
        $occupied = (int) ($bedCounts[IpdBed::STATUS_OCCUPIED] ?? 0);
        $operational = $totalBeds - (int) ($bedCounts[IpdBed::STATUS_OUT_OF_SERVICE] ?? 0) - (int) ($bedCounts[IpdBed::STATUS_MAINTENANCE] ?? 0);

        return ApiResponse::success([
            'admissions_today' => IpdAdmission::query()->forTenant($companyId, $branchId)->whereDate('admitted_at', $today)->count(),
            'discharges_today' => IpdAdmission::query()->forTenant($companyId, $branchId)->whereDate('actual_discharge_date', $today)->count(),
            'current_inpatients' => IpdAdmission::query()->forTenant($companyId, $branchId)->whereIn('status', ['admitted', 'active', 'transfer_requested', 'transferred', 'discharge_planned', 'discharge_pending'])->count(),
            'pending_admission_requests' => IpdAdmissionRequest::query()->forTenant($companyId, $branchId)->whereIn('status', [IpdAdmissionRequest::STATUS_REQUESTED, IpdAdmissionRequest::STATUS_PENDING_APPROVAL])->count(),
            'delayed_discharges' => IpdAdmission::query()->forTenant($companyId, $branchId)->where('status', 'active')->whereNotNull('expected_discharge_date')->where('expected_discharge_date', '<', $today)->count(),
            'total_beds' => $totalBeds,
            'available_beds' => (int) ($bedCounts[IpdBed::STATUS_AVAILABLE] ?? 0),
            'occupied_beds' => $occupied,
            'occupancy_rate' => $operational > 0 ? round(($occupied / $operational) * 100, 1) : 0.0,
        ]);
    }
}
