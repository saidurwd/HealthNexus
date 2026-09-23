<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Laboratory\LabCriticalResultAlert;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabSpecimen;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class LabDashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', LabOrder::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $today = now()->toDateString();

        $summary = [
            'orders_today' => LabOrder::query()->forTenant($companyId, $branchId)->whereDate('ordered_at', $today)->count(),
            'specimens_collected_today' => LabSpecimen::query()->forTenant($companyId, $branchId)->whereDate('collected_at', $today)->count(),
            'specimens_pending' => LabSpecimen::query()->forTenant($companyId, $branchId)->whereIn('status', ['pending', 'collected', 'in_transit'])->count(),
            'specimens_rejected_today' => LabSpecimen::query()->forTenant($companyId, $branchId)->whereDate('rejected_at', $today)->count(),
            'orders_awaiting_validation' => LabOrder::query()->forTenant($companyId, $branchId)->where('status', 'awaiting_validation')->count(),
            'reports_completed_today' => LabOrder::query()->forTenant($companyId, $branchId)->where('status', 'reported')->whereDate('updated_at', $today)->count(),
            'critical_unacknowledged' => LabCriticalResultAlert::query()->forTenant($companyId, $branchId)->whereNull('acknowledged_at')->count(),
        ];

        return view('admin.lab.dashboard.index', compact('summary'));
    }
}
