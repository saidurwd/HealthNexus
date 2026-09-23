<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacyDispensing;
use App\Models\Pharmacy\PharmacyOrder;
use App\Models\Pharmacy\PharmacyStore;
use App\Services\AuditLogger;
use App\Services\Pharmacy\PharmacyDispensingService;
use App\Services\Pharmacy\PharmacyReturnService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PharmacyDispensingController extends Controller
{
    public function __construct(
        private readonly PharmacyDispensingService $dispensing,
        private readonly PharmacyReturnService $returns,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PharmacyDispensing::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $dispensings = PharmacyDispensing::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'store'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('dispensed_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pharmacy.dispensing.index', compact('dispensings'));
    }

    public function create(PharmacyOrder $order)
    {
        $this->authorize('create', PharmacyDispensing::class);

        $order->load(['patient', 'items.medication']);
        $stores = PharmacyStore::query()->forTenant($order->company_id, $order->branch_id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.pharmacy.dispensing.create', compact('order', 'stores'));
    }

    public function store(Request $request, PharmacyOrder $order)
    {
        $this->authorize('create', PharmacyDispensing::class);

        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:pharmacy_stores,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.order_item_id' => ['required', 'integer', 'exists:pharmacy_order_items,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.medication_id' => ['nullable', 'integer', 'exists:pharmacy_medications,id'],
            'lines.*.substitution_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $store = PharmacyStore::query()->findOrFail($validated['store_id']);

        try {
            $dispensing = $this->dispensing->dispense($order, $store, $validated['lines'], $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $this->auditLogger->log('CREATE', PharmacyDispensing::class, $dispensing->id, null, $dispensing->toArray(), $request);

        return redirect()->route('admin.pharmacy.dispensing.show', $dispensing)->with('success', 'Medications dispensed.');
    }

    public function show(PharmacyDispensing $dispensing)
    {
        $this->authorize('view', $dispensing);

        $dispensing->load(['patient', 'store', 'order', 'items.medication', 'items.batch']);

        return view('admin.pharmacy.dispensing.show', compact('dispensing'));
    }

    public function verify(Request $request, PharmacyDispensing $dispensing)
    {
        $this->authorize('verify', $dispensing);

        $oldValues = $dispensing->toArray();
        $this->dispensing->verify($dispensing, $request->user());

        $this->auditLogger->log('VERIFY', PharmacyDispensing::class, $dispensing->id, $oldValues, $dispensing->fresh()->toArray(), $request);

        return redirect()->route('admin.pharmacy.dispensing.show', $dispensing)->with('success', 'Dispensing verified.');
    }

    public function storeReturn(Request $request, PharmacyDispensing $dispensing)
    {
        $this->authorize('return', $dispensing);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.dispensing_item_id' => ['required', 'integer', 'exists:pharmacy_dispensing_items,id'],
            'items.*.quantity_returned' => ['required', 'integer', 'min:1'],
        ]);

        $return = $this->returns->request($dispensing, $validated['items'], $validated['reason'], $request->user());

        $this->auditLogger->log('CREATE', \App\Models\Pharmacy\PharmacyReturn::class, $return->id, null, $return->toArray(), $request);

        return redirect()->route('admin.pharmacy.dispensing.show', $dispensing)->with('success', 'Return requested.');
    }
}
