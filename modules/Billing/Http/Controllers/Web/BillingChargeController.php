<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingCharge;
use App\Services\AuditLogger;
use App\Services\Billing\ChargeService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class BillingChargeController extends Controller
{
    public function __construct(
        private readonly ChargeService $charges,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', BillingCharge::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $charges = BillingCharge::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'billingItem'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('charged_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.billing.charges.index', compact('charges'));
    }

    public function show(BillingCharge $charge)
    {
        $this->authorize('view', $charge);

        $charge->load(['patient', 'billingItem', 'encounter', 'source', 'invoiceItem.invoice']);

        return view('admin.billing.charges.show', compact('charge'));
    }

    public function cancel(Request $request, BillingCharge $charge)
    {
        $this->authorize('cancel', $charge);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $oldValues = $charge->toArray();

        $this->charges->cancel($charge, $validated['reason'], $request->user());

        $this->auditLogger->log('CANCEL', BillingCharge::class, $charge->id, $oldValues, $charge->fresh()->toArray(), $request);

        return redirect()->route('admin.billing.charges.show', $charge)->with('success', 'Charge cancelled successfully.');
    }
}
