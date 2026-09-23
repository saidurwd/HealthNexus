<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyDispensing;
use App\Models\Pharmacy\PharmacyOrder;
use App\Models\Pharmacy\PharmacySafetyAlert;
use App\Services\SettingsService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PharmacyDashboardController extends Controller
{
    public function index(Request $request, SettingsService $settings)
    {
        Gate::authorize('pharmacy.dashboard.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();
        $today = now()->toDateString();
        $thresholdDays = (int) $settings->get('pharmacy.near_expiry_threshold_days', 90);

        $summary = [
            'orders_pending' => PharmacyOrder::query()->forTenant($companyId, $branchId)->where('status', 'pending')->count(),
            'orders_under_review' => PharmacyOrder::query()->forTenant($companyId, $branchId)->where('status', 'under_review')->count(),
            'dispensed_today' => PharmacyDispensing::query()->forTenant($companyId, $branchId)->whereDate('dispensed_at', $today)->count(),
            'unresolved_safety_alerts' => PharmacySafetyAlert::query()->forTenant($companyId, $branchId)->where('is_overridden', false)->count(),
            'near_expiry_batches' => PharmacyBatch::query()
                ->where('company_id', $companyId)
                ->where('is_quarantined', false)
                ->where('expiry_date', '<=', now()->addDays($thresholdDays)->toDateString())
                ->where('expiry_date', '>', $today)
                ->count(),
            'quarantined_batches' => PharmacyBatch::query()->where('company_id', $companyId)->where('is_quarantined', true)->count(),
        ];

        return view('admin.pharmacy.dashboard.index', compact('summary'));
    }
}
