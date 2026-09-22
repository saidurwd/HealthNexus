<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingCategory;
use App\Models\Branch;
use App\Models\Company;
use App\Services\AuditLogger;
use App\Services\Billing\BillingItemService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Billing\Http\Requests\Web\StoreBillingCategoryRequest;
use Modules\Billing\Http\Requests\Web\UpdateBillingCategoryRequest;

class BillingCategoryController extends Controller
{
    public function __construct(
        private readonly BillingItemService $items,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', BillingCategory::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $categories = BillingCategory::query()
            ->forTenant($companyId, $branchId)
            ->orderBy('name')
            ->paginate(20);

        return view('admin.billing.categories.index', compact('categories'));
    }

    public function create()
    {
        $this->authorize('create', BillingCategory::class);

        $companies = Company::all();
        $branches = Branch::all();

        return view('admin.billing.categories.create', compact('companies', 'branches'));
    }

    public function store(StoreBillingCategoryRequest $request)
    {
        $this->authorize('create', BillingCategory::class);

        $category = $this->items->createCategory($request->validated(), $request->user());

        $this->auditLogger->log('CREATE', BillingCategory::class, $category->id, null, $category->toArray(), $request);

        return redirect()->route('admin.billing.categories.index')->with('success', 'Billing category created successfully.');
    }

    public function edit(BillingCategory $category)
    {
        $this->authorize('update', $category);

        return view('admin.billing.categories.edit', compact('category'));
    }

    public function update(UpdateBillingCategoryRequest $request, BillingCategory $category)
    {
        $this->authorize('update', $category);

        $oldValues = $category->toArray();

        $this->items->updateCategory($category, $request->validated(), $request->user());

        $this->auditLogger->log('UPDATE', BillingCategory::class, $category->id, $oldValues, $category->toArray(), $request);

        return redirect()->route('admin.billing.categories.index')->with('success', 'Billing category updated successfully.');
    }

    public function destroy(Request $request, BillingCategory $category)
    {
        $this->authorize('delete', $category);

        $oldValues = $category->toArray();

        $this->items->deactivateCategory($category, $request->user());

        $this->auditLogger->log('DELETE', BillingCategory::class, $category->id, $oldValues, null, $request);

        return redirect()->route('admin.billing.categories.index')->with('success', 'Billing category deactivated successfully.');
    }
}
