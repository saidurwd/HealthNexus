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
        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $todaySummary = $this->revenue->todaysSummary($companyId, $branchId);
        $cashierSummary = $this->revenue->cashierSummary($companyId, $branchId);

        return view('admin.billing.dashboard.index', compact('todaySummary', 'cashierSummary'));
    }
}
