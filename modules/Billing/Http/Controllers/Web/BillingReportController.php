<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingInvoice;
use App\Services\Billing\RevenueService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BillingReportController extends Controller
{
    public function __construct(private readonly RevenueService $revenue) {}

    public function billing(Request $request)
    {
        Gate::authorize('billing.report.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $invoices = BillingInvoice::query()
            ->forTenant($companyId, $branchId)
            ->with('patient')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('invoice_date')
            ->paginate(30);

        return view('admin.billing.reports.billing', compact('invoices'));
    }

    public function collection(Request $request)
    {
        Gate::authorize('billing.report.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $summary = $this->revenue->cashierSummary($companyId, $branchId);

        return view('admin.billing.reports.collection', compact('summary'));
    }

    public function revenue(Request $request)
    {
        Gate::authorize('billing.report.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $byCategory = $this->revenue->revenueByCategory($companyId, $branchId, $from, $to);

        return view('admin.billing.reports.revenue', compact('byCategory', 'from', 'to'));
    }

    public function receivables(Request $request)
    {
        Gate::authorize('billing.report.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $byPatient = $this->revenue->outstandingByPatient($companyId, $branchId);
        $byCorporate = $this->revenue->outstandingByCorporate($companyId, $branchId);

        return view('admin.billing.reports.receivables', compact('byPatient', 'byCorporate'));
    }

    public function refunds(Request $request)
    {
        Gate::authorize('billing.report.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $refunds = $this->revenue->refundSummary($companyId, $branchId, $from, $to);

        return view('admin.billing.reports.refunds', compact('refunds', 'from', 'to'));
    }

    public function discounts(Request $request)
    {
        Gate::authorize('billing.report.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $discounts = $this->revenue->discountSummary($companyId, $branchId, $from, $to);

        return view('admin.billing.reports.discounts', compact('discounts', 'from', 'to'));
    }
}
