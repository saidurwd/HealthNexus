<?php

namespace Modules\Icu\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Icu\IcuCriticalAlert;
use App\Models\Icu\IcuEpisode;
use App\Services\Icu\IcuAlertService;
use App\Services\Icu\IcuObservationService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IcuClinicalApiController extends Controller
{
    public function __construct(
        private readonly IcuObservationService $observations,
        private readonly IcuAlertService $alerts,
    ) {}

    public function observations(IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        return ApiResponse::paginated($episode->observations()->latest('observed_at')->paginate(50));
    }

    public function storeObservation(Request $request, IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        $validated = $request->validate([
            'observation_type' => ['required', 'string'],
            'value_numeric' => ['nullable', 'numeric'],
            'value_text' => ['nullable', 'string'],
            'unit' => ['nullable', 'string'],
        ]);

        $observation = $this->observations->record($episode, $validated, $request->user());

        return ApiResponse::success($observation, 'Observation recorded.');
    }

    public function alerts(Request $request)
    {
        $this->authorize('viewAny', IcuCriticalAlert::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        return ApiResponse::paginated(
            IcuCriticalAlert::forTenant($companyId, $branchId)->whereNull('acknowledged_at')->with('episode.patient')->latest('triggered_at')->paginate(20)
        );
    }

    public function acknowledgeAlert(Request $request, IcuCriticalAlert $alert)
    {
        $this->authorize('acknowledge', $alert);

        try {
            $alert = $this->alerts->acknowledge($alert, $request->user(), $request->input('action'));
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        return ApiResponse::success($alert, 'Alert acknowledged.');
    }
}
