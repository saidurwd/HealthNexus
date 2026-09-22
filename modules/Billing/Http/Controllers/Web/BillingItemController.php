<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingTaxCategory;
use App\Models\Branch;
use App\Models\Company;
use App\Services\AuditLogger;
use App\Services\Billing\BillingItemService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Billing\Http\Requests\Web\StoreBillingItemRequest;
use Modules\Billing\Http\Requests\Web\UpdateBillingItemRequest;

class BillingItemController extends Controller
{
    public function __construct(
        private readonly BillingItemService $items,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', BillingItem::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $items = BillingItem::query()
            ->forTenant($companyId, $branchId)
            ->with('category')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('name', 'like', '%'.$request->input('search').'%')
                    ->orWhere('item_code', 'like', '%'.$request->input('search').'%');
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.billing.items.index', compact('items'));
    }

    public function create()
    {
        $this->authorize('create', BillingItem::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $categories = BillingCategory::query()->forTenant($companyId)->orderBy('name')->get();
        $taxCategories = BillingTaxCategory::query()->forTenant($companyId)->orderBy('name')->get();
        $companies = Company::all();
        $branches = Branch::all();

        return view('admin.billing.items.create', compact('categories', 'taxCategories', 'companies', 'branches'));
    }

    public function store(StoreBillingItemRequest $request)
    {
        $this->authorize('create', BillingItem::class);

        $item = $this->items->createItem($request->validated(), $request->user());

        $this->auditLogger->log('CREATE', BillingItem::class, $item->id, null, $item->toArray(), $request);

        return redirect()->route('admin.billing.items.index')->with('success', 'Billing item created successfully.');
    }

    public function show(BillingItem $item)
    {
        $this->authorize('view', $item);

        $item->load(['category', 'taxCategory', 'priceListItems.priceList']);

        return view('admin.billing.items.show', compact('item'));
    }

    public function edit(BillingItem $item)
    {
        $this->authorize('update', $item);

        $categories = BillingCategory::query()->forTenant($item->company_id)->orderBy('name')->get();
        $taxCategories = BillingTaxCategory::query()->forTenant($item->company_id)->orderBy('name')->get();

        return view('admin.billing.items.edit', compact('item', 'categories', 'taxCategories'));
    }

    public function update(UpdateBillingItemRequest $request, BillingItem $item)
    {
        $this->authorize('update', $item);

        $oldValues = $item->toArray();

        $this->items->updateItem($item, $request->validated(), $request->user());

        $this->auditLogger->log('UPDATE', BillingItem::class, $item->id, $oldValues, $item->toArray(), $request);

        return redirect()->route('admin.billing.items.index')->with('success', 'Billing item updated successfully.');
    }

    public function destroy(Request $request, BillingItem $item)
    {
        $this->authorize('delete', $item);

        $oldValues = $item->toArray();

        $this->items->deactivateItem($item, $request->user());

        $this->auditLogger->log('DELETE', BillingItem::class, $item->id, $oldValues, null, $request);

        return redirect()->route('admin.billing.items.index')->with('success', 'Billing item deactivated successfully.');
    }
}
