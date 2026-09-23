<?php

namespace Modules\Radiology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Radiology\RadiologyWorklistService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RadiologyWorklistController extends Controller
{
    public function __construct(private readonly RadiologyWorklistService $worklist) {}

    public function index(Request $request)
    {
        Gate::authorize('radiology.worklist.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $examinations = $this->worklist->query($companyId, $branchId, $request->only([
            'modality_id', 'priority', 'status', 'date', 'assigned_radiologist_id', 'unassigned', 'accession_number',
        ]));

        return ApiResponse::paginated($examinations);
    }
}
