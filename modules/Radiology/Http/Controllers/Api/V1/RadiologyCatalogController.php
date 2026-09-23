<?php

namespace Modules\Radiology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Radiology\RadiologyModality;
use App\Models\Radiology\RadiologyProcedure;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RadiologyCatalogController extends Controller
{
    public function procedures(Request $request)
    {
        $this->authorize('viewAny', RadiologyProcedure::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $procedures = RadiologyProcedure::query()
            ->forTenant($companyId, $branchId)
            ->where('is_active', true)
            ->with(['section', 'bodyPart'])
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($procedures);
    }

    public function modalities(Request $request)
    {
        Gate::authorize('radiology.modality.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $modalities = RadiologyModality::query()
            ->forTenant($companyId, $branchId)
            ->where('is_active', true)
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($modalities);
    }
}
