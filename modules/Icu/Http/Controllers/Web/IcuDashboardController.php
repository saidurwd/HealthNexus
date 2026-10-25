<?php

namespace Modules\Icu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Icu\IcuAdmissionRequest;
use App\Models\Icu\IcuCriticalAlert;
use App\Models\Icu\IcuEpisode;
use App\Models\Ipd\IpdBed;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IcuDashboardController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('icu.dashboard.view');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $metrics = [
            'current_patients' => IcuEpisode::forTenant($organizationId, $branchId)->whereNull('discharged_at')->count(),
            'pending_requests' => IcuAdmissionRequest::forTenant($organizationId, $branchId)->whereIn('status', [IcuAdmissionRequest::STATUS_REQUESTED, IcuAdmissionRequest::STATUS_PENDING_APPROVAL])->count(),
            'unacknowledged_alerts' => IcuCriticalAlert::forTenant($organizationId, $branchId)->whereNull('acknowledged_at')->count(),
            'beds_available' => IpdBed::forTenant($organizationId, $branchId)->where('icu_capable', true)->where('status', IpdBed::STATUS_AVAILABLE)->count(),
            'beds_occupied' => IpdBed::forTenant($organizationId, $branchId)->where('icu_capable', true)->where('status', IpdBed::STATUS_OCCUPIED)->count(),
            'admissions_today' => IcuEpisode::forTenant($organizationId, $branchId)->whereDate('admitted_at', now()->toDateString())->count(),
            'discharges_today' => IcuEpisode::forTenant($organizationId, $branchId)->whereDate('discharged_at', now()->toDateString())->count(),
        ];

        $criticalAlerts = IcuCriticalAlert::forTenant($organizationId, $branchId)
            ->whereNull('acknowledged_at')
            ->with('episode.patient')
            ->latest('triggered_at')
            ->limit(10)
            ->get();

        return view('admin.icu.dashboard.index', compact('metrics', 'criticalAlerts'));
    }
}
