<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingAdjustment;
use App\Models\Billing\BillingInvoice;
use App\Services\AuditLogger;
use App\Services\Billing\AdjustmentService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Billing\Http\Requests\Web\RequestAdjustmentRequest;

class BillingAdjustmentController extends Controller
{
    public function __construct(
        private readonly AdjustmentService $adjustments,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', BillingAdjustment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $adjustments = BillingAdjustment::query()
            ->forTenant($companyId, $branchId)
            ->with('invoice.patient')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('requested_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.billing.adjustments.index', compact('adjustments'));
    }

    public function create(Request $request)
    {
        $this->authorize('request', BillingAdjustment::class);

        $invoice = $request->filled('invoice_id') ? BillingInvoice::findOrFail($request->input('invoice_id')) : null;

        return view('admin.billing.adjustments.create', compact('invoice'));
    }

    public function store(RequestAdjustmentRequest $request)
    {
        $this->authorize('request', BillingAdjustment::class);

        $invoice = BillingInvoice::findOrFail($request->validated('invoice_id'));

        $adjustment = $this->adjustments->request(
            $invoice,
            $request->validated('type'),
            (string) $request->validated('original_value'),
            (string) $request->validated('new_value'),
            $request->validated('reason'),
            $request->user(),
        );

        $this->auditLogger->log('CREATE', BillingAdjustment::class, $adjustment->id, null, $adjustment->toArray(), $request);

        return redirect()->route('admin.billing.adjustments.show', $adjustment)->with('success', 'Adjustment requested successfully.');
    }

    public function show(BillingAdjustment $adjustment)
    {
        $this->authorize('view', $adjustment);

        $adjustment->load(['invoice.patient', 'requestedBy', 'approvedBy']);
        $requiresApproval = $this->adjustments->requiresApproval($adjustment);

        return view('admin.billing.adjustments.show', compact('adjustment', 'requiresApproval'));
    }

    public function approve(Request $request, BillingAdjustment $adjustment)
    {
        $this->authorize('approve', $adjustment);

        $validated = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $oldValues = $adjustment->toArray();

        $this->adjustments->approve($adjustment, $request->user(), $validated['note'] ?? null);

        $this->auditLogger->log('APPROVE', BillingAdjustment::class, $adjustment->id, $oldValues, $adjustment->fresh()->toArray(), $request);

        return redirect()->route('admin.billing.adjustments.show', $adjustment)->with('success', 'Adjustment approved and applied.');
    }
}
