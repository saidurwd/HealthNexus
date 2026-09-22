<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingRefund;
use App\Services\AuditLogger;
use App\Services\Billing\RefundService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Billing\Http\Requests\Web\RequestRefundRequest;

class BillingRefundController extends Controller
{
    public function __construct(
        private readonly RefundService $refunds,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', BillingRefund::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $refunds = BillingRefund::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'payment'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('requested_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.billing.refunds.index', compact('refunds'));
    }

    public function create(Request $request)
    {
        $this->authorize('request', BillingRefund::class);

        $payment = $request->filled('payment_id') ? BillingPayment::findOrFail($request->input('payment_id')) : null;

        return view('admin.billing.refunds.create', compact('payment'));
    }

    public function store(RequestRefundRequest $request)
    {
        $this->authorize('request', BillingRefund::class);

        $payment = BillingPayment::findOrFail($request->validated('payment_id'));

        $refund = $this->refunds->request($payment, (string) $request->validated('amount'), $request->validated('reason'), $request->user());

        $this->auditLogger->log('CREATE', BillingRefund::class, $refund->id, null, $refund->toArray(), $request);

        return redirect()->route('admin.billing.refunds.show', $refund)->with('success', 'Refund requested successfully.');
    }

    public function show(BillingRefund $refund)
    {
        $this->authorize('view', $refund);

        $refund->load(['patient', 'payment', 'invoice', 'requestedBy', 'approvedBy', 'processedBy']);

        return view('admin.billing.refunds.show', compact('refund'));
    }

    public function approve(Request $request, BillingRefund $refund)
    {
        $this->authorize('approve', $refund);

        $validated = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $oldValues = $refund->toArray();

        $this->refunds->approve($refund, $request->user(), $validated['note'] ?? null);

        $this->auditLogger->log('APPROVE', BillingRefund::class, $refund->id, $oldValues, $refund->fresh()->toArray(), $request);

        return redirect()->route('admin.billing.refunds.show', $refund)->with('success', 'Refund approved.');
    }

    public function process(Request $request, BillingRefund $refund)
    {
        $this->authorize('process', $refund);

        $validated = $request->validate(['processor_reference' => ['nullable', 'string', 'max:255']]);

        $oldValues = $refund->toArray();

        $this->refunds->process($refund, $request->user(), $validated['processor_reference'] ?? null);

        $this->auditLogger->log('PROCESS', BillingRefund::class, $refund->id, $oldValues, $refund->fresh()->toArray(), $request);

        return redirect()->route('admin.billing.refunds.show', $refund)->with('success', 'Refund processed.');
    }
}
