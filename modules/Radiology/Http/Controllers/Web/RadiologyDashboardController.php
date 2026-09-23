<?php

namespace Modules\Radiology\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Radiology\RadiologyCriticalFinding;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyOrder;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class RadiologyDashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyOrder::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $today = now()->toDateString();

        $summary = [
            'orders_today' => RadiologyOrder::query()->forTenant($companyId, $branchId)->whereDate('ordered_at', $today)->count(),
            'scheduled_today' => RadiologyExamination::query()->forTenant($companyId, $branchId)->whereDate('scheduled_at', $today)->count(),
            'waiting_patients' => RadiologyExamination::query()->forTenant($companyId, $branchId)->whereIn('status', ['checked_in', 'preparing', 'ready'])->count(),
            'in_progress' => RadiologyExamination::query()->forTenant($companyId, $branchId)->where('status', 'in_progress')->count(),
            'completed_today' => RadiologyExamination::query()->forTenant($companyId, $branchId)->where('status', 'completed')->whereDate('completed_at', $today)->count(),
            'awaiting_reporting' => RadiologyOrder::query()->forTenant($companyId, $branchId)->whereIn('status', ['completed', 'images_available'])->count(),
            'urgent_studies' => RadiologyExamination::query()->forTenant($companyId, $branchId)->whereHas('orderItem', fn ($q) => $q->whereIn('priority', ['urgent', 'stat']))->whereIn('status', ['completed', 'images_available'])->count(),
            'critical_unacknowledged' => RadiologyCriticalFinding::query()->forTenant($companyId, $branchId)->whereNull('acknowledged_at')->count(),
            'reports_pending_approval' => \App\Models\Radiology\RadiologyReport::query()->forTenant($companyId, $branchId)->where('status', 'submitted')->count(),
            'reports_completed_today' => \App\Models\Radiology\RadiologyReport::query()->forTenant($companyId, $branchId)->where('status', 'final')->whereDate('approved_at', $today)->count(),
        ];

        return view('admin.radiology.dashboard.index', compact('summary'));
    }
}
