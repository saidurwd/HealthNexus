<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyStock;
use App\Models\Pharmacy\PharmacyStockTransaction;
use App\Models\Pharmacy\PharmacyStore;
use App\Services\AuditLogger;
use App\Services\Pharmacy\PharmacyStockService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PharmacyStockController extends Controller
{
    public function __construct(
        private readonly PharmacyStockService $stock,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PharmacyStore::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $stores = PharmacyStore::query()->forTenant($companyId, $branchId)->where('is_active', true)->orderBy('name')->get();
        $storeId = $request->integer('store_id') ?: $stores->first()?->id;

        $stockRows = PharmacyStock::query()
            ->where('store_id', $storeId)
            ->where('quantity_available', '>', 0)
            ->with(['medication', 'batch'])
            ->when($request->filled('search'), fn ($q) => $q->whereHas('medication', fn ($m) => $m->where('name', 'like', '%'.$request->input('search').'%')))
            ->orderBy('quantity_available')
            ->paginate(25)
            ->withQueryString();

        return view('admin.pharmacy.stock.index', compact('stores', 'storeId', 'stockRows'));
    }

    public function batches(Request $request)
    {
        $this->authorize('viewAny', PharmacyBatch::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $batches = PharmacyBatch::query()
            ->where('company_id', $companyId)
            ->with('medication')
            ->when($request->filled('search'), fn ($q) => $q->where('batch_number', 'like', '%'.$request->input('search').'%'))
            ->when($request->boolean('near_expiry'), fn ($q) => $q->where('expiry_date', '<=', now()->addDays(90)->toDateString()))
            ->orderBy('expiry_date')
            ->paginate(25)
            ->withQueryString();

        return view('admin.pharmacy.stock.batches', compact('batches'));
    }

    public function receiveForm()
    {
        \Illuminate\Support\Facades\Gate::authorize('pharmacy.stock.receive');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $stores = PharmacyStore::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $medications = PharmacyMedication::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();

        return view('admin.pharmacy.stock.receive', compact('stores', 'medications'));
    }

    public function receive(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:pharmacy_stores,id'],
            'medication_id' => ['required', 'integer', 'exists:pharmacy_medications,id'],
            'batch_number' => ['required', 'string', 'max:100'],
            'manufacturing_date' => ['nullable', 'date'],
            'expiry_date' => ['required', 'date', 'after:today'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'supplier_reference' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $store = PharmacyStore::query()->findOrFail($validated['store_id']);
        $this->authorize('receive', $store);

        $medication = PharmacyMedication::query()->findOrFail($validated['medication_id']);

        $batch = $this->stock->receiveNewBatch($store, $medication, [
            'batch_number' => $validated['batch_number'],
            'manufacturing_date' => $validated['manufacturing_date'] ?? null,
            'expiry_date' => $validated['expiry_date'],
            'unit_cost' => $validated['unit_cost'] ?? null,
            'selling_price' => $validated['selling_price'] ?? null,
            'supplier_reference' => $validated['supplier_reference'] ?? null,
        ], $validated['quantity'], $request->user());

        $this->auditLogger->log('RECEIVE', PharmacyBatch::class, $batch->id, null, $batch->toArray(), $request);

        return redirect()->route('admin.pharmacy.stock.index', ['store_id' => $store->id])->with('success', 'Stock received.');
    }

    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:pharmacy_stores,id'],
            'batch_id' => ['required', 'integer', 'exists:pharmacy_batches,id'],
            'quantity_delta' => ['required', 'integer', 'not_in:0'],
            'type' => ['required', 'string', 'in:adjustment,damage,expired,wastage,correction'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $store = PharmacyStore::query()->findOrFail($validated['store_id']);
        $this->authorize('adjust', $store);

        $batch = PharmacyBatch::query()->findOrFail($validated['batch_id']);

        try {
            $this->stock->adjust($store, $batch, $validated['quantity_delta'], $validated['type'], $validated['reason'], $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $this->auditLogger->log('ADJUST', PharmacyStockTransaction::class, null, null, $validated, $request);

        return redirect()->route('admin.pharmacy.stock.index', ['store_id' => $store->id])->with('success', 'Stock adjusted.');
    }
}
