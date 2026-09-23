<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacyStockCount;
use App\Models\Pharmacy\PharmacyStore;
use App\Services\AuditLogger;
use App\Services\Pharmacy\PharmacyStockCountService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PharmacyStockCountController extends Controller
{
    public function __construct(
        private readonly PharmacyStockCountService $counts,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('pharmacy.stock.count');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $stockCounts = PharmacyStockCount::query()
            ->forTenant($companyId, $branchId)
            ->with('store')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.pharmacy.stock-count.index', compact('stockCounts'));
    }

    public function start(Request $request)
    {
        Gate::authorize('pharmacy.stock.count');

        $validated = $request->validate(['store_id' => ['required', 'integer', 'exists:pharmacy_stores,id']]);
        $store = PharmacyStore::query()->findOrFail($validated['store_id']);

        $count = $this->counts->start($store, $request->user());

        $this->auditLogger->log('CREATE', PharmacyStockCount::class, $count->id, null, $count->toArray(), $request);

        return redirect()->route('admin.pharmacy.stock-count.show', $count)->with('success', 'Stock count started.');
    }

    public function show(PharmacyStockCount $stockCount)
    {
        Gate::authorize('pharmacy.stock.count');

        $stockCount->load(['store', 'items.medication', 'items.batch']);

        return view('admin.pharmacy.stock-count.show', compact('stockCount'));
    }

    public function recordCount(Request $request, PharmacyStockCount $stockCount)
    {
        Gate::authorize('pharmacy.stock.count');

        $validated = $request->validate([
            'counts' => ['required', 'array', 'min:1'],
            'counts.*.stock_count_item_id' => ['required', 'integer', 'exists:pharmacy_stock_count_items,id'],
            'counts.*.counted_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $this->counts->recordCount($stockCount, $validated['counts']);

        $this->auditLogger->log('RECORD_COUNT', PharmacyStockCount::class, $stockCount->id, null, $validated, $request);

        return redirect()->route('admin.pharmacy.stock-count.show', $stockCount)->with('success', 'Counts recorded.');
    }

    public function complete(Request $request, PharmacyStockCount $stockCount)
    {
        Gate::authorize('pharmacy.stock.count');

        $oldValues = $stockCount->toArray();

        try {
            $this->counts->complete($stockCount, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('COMPLETE', PharmacyStockCount::class, $stockCount->id, $oldValues, $stockCount->fresh()->toArray(), $request);

        return redirect()->route('admin.pharmacy.stock-count.show', $stockCount)->with('success', 'Stock count completed.');
    }

    public function applyAdjustments(Request $request, PharmacyStockCount $stockCount)
    {
        Gate::authorize('pharmacy.stock.adjust');

        $oldValues = $stockCount->toArray();
        $this->counts->applyAdjustments($stockCount, $request->user());

        $this->auditLogger->log('APPLY_ADJUSTMENTS', PharmacyStockCount::class, $stockCount->id, $oldValues, $stockCount->fresh()->toArray(), $request);

        return redirect()->route('admin.pharmacy.stock-count.show', $stockCount)->with('success', 'Variances applied to stock.');
    }
}
