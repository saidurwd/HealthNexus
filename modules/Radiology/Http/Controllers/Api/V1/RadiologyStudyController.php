<?php

namespace Modules\Radiology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Radiology\RadiologyStudy;
use App\Services\Radiology\RadiologyStudyService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RadiologyStudyController extends Controller
{
    public function __construct(private readonly RadiologyStudyService $studies) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyStudy::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $studies = RadiologyStudy::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'examination.orderItem.procedure'])
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($studies);
    }

    public function show(RadiologyStudy $study)
    {
        $this->authorize('view', $study);

        $study->load(['patient', 'examination.orderItem.procedure', 'series.instances']);

        return ApiResponse::success($study);
    }

    public function viewer(Request $request, RadiologyStudy $study)
    {
        $this->authorize('view', $study);

        try {
            $url = $this->studies->viewerUrl($study, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        return ApiResponse::success(['viewer_url' => $url]);
    }
}
