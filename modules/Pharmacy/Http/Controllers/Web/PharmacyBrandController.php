<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacyBrand;
use App\Models\Pharmacy\PharmacyGeneric;
use App\Services\AuditLogger;
use App\Services\Pharmacy\PharmacyBrandService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PharmacyBrandController extends Controller
{
    public function __construct(
        private readonly PharmacyBrandService $brands,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('pharmacy.brand.view');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $brands = PharmacyBrand::query()
            ->forTenant($organizationId, $branchId)
            ->with('generic')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pharmacy.brands.index', compact('brands'));
    }

    public function create()
    {
        Gate::authorize('pharmacy.brand.create');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $generics = PharmacyGeneric::query()->forTenant($organizationId)->where('is_active', true)->orderBy('generic_name')->get();

        return view('admin.pharmacy.brands.create', compact('generics'));
    }

    public function store(Request $request)
    {
        Gate::authorize('pharmacy.brand.create');

        $validated = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'generic_id' => ['nullable', 'integer', 'exists:pharmacy_generics,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $brand = $this->brands->create($validated, $request->user());

        $this->auditLogger->log('CREATE', PharmacyBrand::class, $brand->id, null, $brand->toArray(), $request);

        return redirect()->route('admin.pharmacy.brands.index')->with('success', __('Brand created.'));
    }

    public function edit(PharmacyBrand $brand)
    {
        Gate::authorize('pharmacy.brand.update');

        $generics = PharmacyGeneric::query()->forTenant($brand->organization_id)->where('is_active', true)->orderBy('generic_name')->get();

        return view('admin.pharmacy.brands.edit', compact('brand', 'generics'));
    }

    public function update(Request $request, PharmacyBrand $brand)
    {
        Gate::authorize('pharmacy.brand.update');

        $validated = $request->validate([
            'generic_id' => ['nullable', 'integer', 'exists:pharmacy_generics,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $oldValues = $brand->toArray();
        $this->brands->update($brand, $validated, $request->user());

        $this->auditLogger->log('UPDATE', PharmacyBrand::class, $brand->id, $oldValues, $brand->fresh()->toArray(), $request);

        return redirect()->route('admin.pharmacy.brands.index')->with('success', __('Brand updated.'));
    }
}
