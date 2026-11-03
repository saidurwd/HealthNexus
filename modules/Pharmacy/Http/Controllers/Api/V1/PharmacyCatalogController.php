<?php

namespace Modules\Pharmacy\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Pharmacy\PharmacyBrand;
use App\Models\Pharmacy\PharmacyGeneric;
use App\Models\Pharmacy\PharmacyMedication;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PharmacyCatalogController extends Controller
{
    public function medications(Request $request)
    {
        $this->authorize('viewAny', PharmacyMedication::class);

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $medications = PharmacyMedication::query()
            ->forTenant($organizationId, $branchId)
            ->where('is_active', true)
            ->with(['generic', 'brand', 'dosageForm', 'route'])
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($medications);
    }

    public function generics(Request $request)
    {
        Gate::authorize('pharmacy.generic.view');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $generics = PharmacyGeneric::query()
            ->forTenant($organizationId, $branchId)
            ->where('is_active', true)
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($generics);
    }

    public function brands(Request $request)
    {
        Gate::authorize('pharmacy.brand.view');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $brands = PharmacyBrand::query()
            ->forTenant($organizationId, $branchId)
            ->where('is_active', true)
            ->with('generic')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($brands);
    }
}
