<?php

namespace Modules\Ot\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgeryRequest;
use App\Models\Ot\OtTheatre;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OtDashboardController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('ot.dashboard.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();
        $today = now()->toDateString();

        $metrics = [
            'scheduled_today' => OtSurgery::forTenant($companyId, $branchId)->whereDate('scheduled_date', $today)->count(),
            'completed_today' => OtSurgery::forTenant($companyId, $branchId)->whereDate('scheduled_date', $today)->where('status', OtSurgery::STATUS_PROCEDURE_COMPLETED)->count(),
            'in_procedure' => OtSurgery::forTenant($companyId, $branchId)->where('status', OtSurgery::STATUS_IN_PROCEDURE)->count(),
            'in_recovery' => OtSurgery::forTenant($companyId, $branchId)->where('status', OtSurgery::STATUS_RECOVERY)->count(),
            'cancelled_today' => OtSurgery::forTenant($companyId, $branchId)->whereDate('scheduled_date', $today)->where('status', OtSurgery::STATUS_CANCELLED)->count(),
            'pending_requests' => OtSurgeryRequest::forTenant($companyId, $branchId)->whereIn('status', [OtSurgeryRequest::STATUS_REQUESTED, OtSurgeryRequest::STATUS_PENDING_APPROVAL])->count(),
            'theatres_available' => OtTheatre::forTenant($companyId, $branchId)->where('status', OtTheatre::STATUS_AVAILABLE)->count(),
            'theatres_occupied' => OtTheatre::forTenant($companyId, $branchId)->where('status', OtTheatre::STATUS_OCCUPIED)->count(),
        ];

        $todaysList = OtSurgery::forTenant($companyId, $branchId)
            ->whereDate('scheduled_date', $today)
            ->with(['patient', 'theatre', 'primarySurgeon', 'primaryProcedure'])
            ->orderBy('scheduled_start_at')
            ->get();

        return view('admin.ot.dashboard.index', compact('metrics', 'todaysList'));
    }
}
