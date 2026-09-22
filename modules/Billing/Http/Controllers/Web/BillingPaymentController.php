<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingCashierSession;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingPaymentMethod;
use App\Services\AuditLogger;
use App\Services\Billing\PaymentService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Billing\Http\Requests\Web\CollectPaymentRequest;

class BillingPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', BillingPayment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $payments = BillingPayment::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'invoice', 'method'])
            ->latest('payment_date')
            ->paginate(20);

        return view('admin.billing.payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', BillingPayment::class);

        $invoice = $request->filled('invoice_id') ? BillingInvoice::findOrFail($request->input('invoice_id')) : null;
        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $methods = BillingPaymentMethod::query()->forTenant($companyId)->where('is_active', true)->get();

        return view('admin.billing.payments.create', compact('invoice', 'methods'));
    }

    public function store(CollectPaymentRequest $request)
    {
        $this->authorize('create', BillingPayment::class);

        $invoice = BillingInvoice::findOrFail($request->validated('invoice_id'));
        $session = BillingCashierSession::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        $payment = $this->payments->collect($invoice, $request->validated(), $request->user(), $session);

        $this->auditLogger->log('CREATE', BillingPayment::class, $payment->id, null, $payment->toArray(), $request);

        return redirect()->route('admin.billing.payments.show', $payment)->with('success', 'Payment collected successfully.');
    }

    public function show(BillingPayment $payment)
    {
        $this->authorize('view', $payment);

        $payment->load(['patient', 'invoice', 'method', 'receipt', 'refunds']);

        return view('admin.billing.payments.show', compact('payment'));
    }

    public function cancel(Request $request, BillingPayment $payment)
    {
        $this->authorize('cancel', $payment);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $oldValues = $payment->toArray();

        $this->payments->cancel($payment, $validated['reason'], $request->user());

        $this->auditLogger->log('CANCEL', BillingPayment::class, $payment->id, $oldValues, $payment->fresh()->toArray(), $request);

        return redirect()->route('admin.billing.payments.show', $payment)->with('success', 'Payment cancelled.');
    }
}
