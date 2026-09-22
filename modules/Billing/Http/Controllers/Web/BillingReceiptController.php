<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingReceipt;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class BillingReceiptController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', BillingReceipt::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $receipts = BillingReceipt::query()
            ->forTenant($companyId, $branchId)
            ->with(['payment.patient', 'invoice'])
            ->latest('issued_at')
            ->paginate(20);

        return view('admin.billing.receipts.index', compact('receipts'));
    }

    public function show(BillingReceipt $receipt)
    {
        $this->authorize('view', $receipt);

        $receipt->load(['payment.patient', 'payment.method', 'invoice', 'issuedBy', 'branch', 'company']);

        return view('admin.billing.receipts.show', compact('receipt'));
    }
}
