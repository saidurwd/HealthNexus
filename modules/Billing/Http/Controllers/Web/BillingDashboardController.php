<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Billing\RevenueService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class BillingDashboardController extends Controller
{
    public function __construct(private readonly RevenueService $revenue) {}

    public function index(Request $request)
    {
        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $todaySummary = $this->revenue->todaysSummary($organizationId, $branchId);
        $cashierSummary = $this->revenue->cashierSummary($organizationId, $branchId);

        return view('admin.billing.dashboard.index', compact('todaySummary', 'cashierSummary'));
    }
}
