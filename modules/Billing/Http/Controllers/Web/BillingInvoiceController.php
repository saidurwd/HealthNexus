<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingInvoice;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Billing\InvoiceLifecycleService;
use App\Services\Billing\InvoiceService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Billing\Http\Requests\Web\StoreBillingInvoiceRequest;

class BillingInvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly InvoiceLifecycleService $lifecycle,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', BillingInvoice::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $invoices = BillingInvoice::query()
            ->forTenant($companyId, $branchId)
            ->with('patient')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('invoice_number', 'like', '%'.$request->input('search').'%'))
            ->latest('invoice_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.billing.invoices.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', BillingInvoice::class);

        $patient = $request->filled('patient_id') ? Patient::findOrFail($request->input('patient_id')) : null;

        return view('admin.billing.invoices.create', compact('patient'));
    }

    public function store(StoreBillingInvoiceRequest $request)
    {
        $this->authorize('create', BillingInvoice::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $patient = Patient::findOrFail($request->validated('patient_id'));

        $invoice = $this->invoices->createDraft($patient, [
            ...$request->validated(),
            'company_id' => $companyId,
            'branch_id' => $branchId,
        ], $request->user());

        $this->auditLogger->log('CREATE', BillingInvoice::class, $invoice->id, null, $invoice->toArray(), $request);

        return redirect()->route('admin.billing.invoices.show', $invoice)->with('success', 'Draft invoice created. Add charges before finalizing.');
    }

    public function show(BillingInvoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['patient', 'items.billingItem', 'payments', 'refunds', 'adjustments']);

        $uninvoicedCharges = BillingCharge::query()
            ->where('patient_id', $invoice->patient_id)
            ->where('status', 'pending')
            ->with('billingItem')
            ->get();

        return view('admin.billing.invoices.show', compact('invoice', 'uninvoicedCharges'));
    }

    public function addCharges(Request $request, BillingInvoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'charge_ids' => ['required', 'array'],
            'charge_ids.*' => ['integer', 'exists:billing_charges,id'],
        ]);

        $charges = BillingCharge::query()->whereIn('id', $validated['charge_ids'])->get();

        $this->invoices->addCharges($invoice, $charges);

        $this->auditLogger->log('ADD_CHARGES', BillingInvoice::class, $invoice->id, null, ['charge_ids' => $validated['charge_ids']], $request);

        return redirect()->route('admin.billing.invoices.show', $invoice)->with('success', 'Charges added to invoice.');
    }

    public function finalize(Request $request, BillingInvoice $invoice)
    {
        $this->authorize('finalize', $invoice);

        $oldValues = $invoice->toArray();

        $this->lifecycle->finalize($invoice, $request->user());

        $this->auditLogger->log('FINALIZE', BillingInvoice::class, $invoice->id, $oldValues, $invoice->fresh()->toArray(), $request);

        return redirect()->route('admin.billing.invoices.show', $invoice)->with('success', 'Invoice finalized successfully.');
    }

    public function cancel(Request $request, BillingInvoice $invoice)
    {
        $this->authorize('cancel', $invoice);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $oldValues = $invoice->toArray();

        $this->lifecycle->cancel($invoice, $validated['reason'], $request->user());

        $this->auditLogger->log('CANCEL', BillingInvoice::class, $invoice->id, $oldValues, $invoice->fresh()->toArray(), $request);

        return redirect()->route('admin.billing.invoices.show', $invoice)->with('success', 'Invoice cancelled.');
    }

    public function writeOff(Request $request, BillingInvoice $invoice)
    {
        $this->authorize('writeOff', $invoice);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $oldValues = $invoice->toArray();

        $this->lifecycle->writeOff($invoice, $validated['reason'], $request->user());

        $this->auditLogger->log('WRITE_OFF', BillingInvoice::class, $invoice->id, $oldValues, $invoice->fresh()->toArray(), $request);

        return redirect()->route('admin.billing.invoices.show', $invoice)->with('success', 'Invoice written off.');
    }
}
