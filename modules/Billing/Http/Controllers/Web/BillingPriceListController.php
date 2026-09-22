<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPriceList;
use App\Models\Billing\BillingPriceListItem;
use App\Models\Branch;
use App\Models\Company;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Billing\Http\Requests\Web\StoreBillingPriceListItemRequest;
use Modules\Billing\Http\Requests\Web\StoreBillingPriceListRequest;
use Modules\Billing\Http\Requests\Web\UpdateBillingPriceListRequest;

class BillingPriceListController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', BillingPriceList::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $priceLists = BillingPriceList::query()
            ->forTenant($companyId, $branchId)
            ->orderByDesc('priority')
            ->paginate(20);

        return view('admin.billing.price-lists.index', compact('priceLists'));
    }

    public function create()
    {
        $this->authorize('create', BillingPriceList::class);

        $companies = Company::all();
        $branches = Branch::all();

        return view('admin.billing.price-lists.create', compact('companies', 'branches'));
    }

    public function store(StoreBillingPriceListRequest $request)
    {
        $this->authorize('create', BillingPriceList::class);

        $priceList = BillingPriceList::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->auditLogger->log('CREATE', BillingPriceList::class, $priceList->id, null, $priceList->toArray(), $request);

        return redirect()->route('admin.billing.price-lists.index')->with('success', 'Price list created successfully.');
    }

    public function show(BillingPriceList $priceList)
    {
        $this->authorize('view', $priceList);

        $priceList->load(['items.billingItem', 'items.department', 'items.provider', 'items.corporate', 'items.insurancePolicy']);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $billingItems = BillingItem::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();

        return view('admin.billing.price-lists.show', compact('priceList', 'billingItems'));
    }

    public function edit(BillingPriceList $priceList)
    {
        $this->authorize('update', $priceList);

        return view('admin.billing.price-lists.edit', compact('priceList'));
    }

    public function update(UpdateBillingPriceListRequest $request, BillingPriceList $priceList)
    {
        $this->authorize('update', $priceList);

        $oldValues = $priceList->toArray();

        $priceList->update([...$request->validated(), 'updated_by' => $request->user()->id]);

        $this->auditLogger->log('UPDATE', BillingPriceList::class, $priceList->id, $oldValues, $priceList->toArray(), $request);

        return redirect()->route('admin.billing.price-lists.index')->with('success', 'Price list updated successfully.');
    }

    public function storeItem(StoreBillingPriceListItemRequest $request, BillingPriceList $priceList)
    {
        $this->authorize('update', $priceList);

        $data = $request->validated();
        $scopeHash = $this->scopeHash($data);

        $item = $priceList->items()->create([
            ...$data,
            'scope_hash' => $scopeHash,
        ]);

        $this->auditLogger->log('CREATE', BillingPriceListItem::class, $item->id, null, $item->toArray(), $request);

        return redirect()->route('admin.billing.price-lists.show', $priceList)->with('success', 'Price added successfully.');
    }

    public function destroyItem(Request $request, BillingPriceList $priceList, BillingPriceListItem $item)
    {
        $this->authorize('update', $priceList);

        $oldValues = $item->toArray();

        $item->update(['is_active' => false]);

        $this->auditLogger->log('DEACTIVATE', BillingPriceListItem::class, $item->id, $oldValues, $item->toArray(), $request);

        return redirect()->route('admin.billing.price-lists.show', $priceList)->with('success', 'Price deactivated successfully.');
    }

    private function scopeHash(array $data): string
    {
        return hash('sha1', implode('|', [
            $data['billing_item_id'],
            $data['department_id'] ?? 'null',
            $data['provider_id'] ?? 'null',
            $data['corporate_id'] ?? 'null',
            $data['insurance_policy_id'] ?? 'null',
            $data['patient_category'] ?? 'null',
        ]));
    }
}
