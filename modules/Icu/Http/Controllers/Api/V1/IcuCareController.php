<?php

namespace Modules\Icu\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Icu\IcuAdmissionRequest;
use App\Models\Icu\IcuEpisode;
use App\Models\Ipd\IpdBed;
use App\Services\Icu\IcuAdmissionRequestService;
use App\Services\Icu\IcuBedBoardService;
use App\Services\Icu\IcuEpisodeService;
use App\Services\Icu\IcuTransferService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Every action route-model-binds the episode/request and re-authorizes through its policy
 * (company/branch scope) — a client-supplied ID is never trusted (spec §49).
 */
class IcuCareController extends Controller
{
    public function __construct(
        private readonly IcuAdmissionRequestService $requests,
        private readonly IcuEpisodeService $episodes,
        private readonly IcuTransferService $transfers,
        private readonly IcuBedBoardService $bedBoard,
    ) {}

    public function dashboard(Request $request)
    {
        $this->authorize('viewAny', IcuEpisode::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        return ApiResponse::success([
            'current_patients' => IcuEpisode::forTenant($companyId, $branchId)->whereNull('discharged_at')->count(),
            'pending_requests' => IcuAdmissionRequest::forTenant($companyId, $branchId)->whereIn('status', [IcuAdmissionRequest::STATUS_REQUESTED, IcuAdmissionRequest::STATUS_PENDING_APPROVAL])->count(),
            'beds_available' => IpdBed::forTenant($companyId, $branchId)->where('icu_capable', true)->where('status', IpdBed::STATUS_AVAILABLE)->count(),
        ]);
    }

    public function bedBoard(Request $request)
    {
        $this->authorize('viewAny', IcuEpisode::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        return ApiResponse::success($this->bedBoard->board($companyId, $branchId)->values());
    }

    public function episodes(Request $request)
    {
        $this->authorize('viewAny', IcuEpisode::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        return ApiResponse::paginated(
            IcuEpisode::forTenant($companyId, $branchId)->with('patient')->latest('admitted_at')->paginate(20)
        );
    }

    public function showEpisode(IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        return ApiResponse::success($episode->load(['patient', 'icuBed', 'icuUnit']));
    }

    public function admissionRequests(Request $request)
    {
        $this->authorize('viewAny', IcuAdmissionRequest::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        return ApiResponse::paginated(
            IcuAdmissionRequest::forTenant($companyId, $branchId)->with('patient')->latest()->paginate(20)
        );
    }

    public function storeAdmissionRequest(Request $request)
    {
        $this->authorize('create', IcuAdmissionRequest::class);

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'encounter_id' => ['required', 'integer', 'exists:encounters,id'],
            'admission_reason' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
            'is_emergency' => ['nullable', 'boolean'],
            'override_reason' => ['nullable', 'string'],
        ]);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        try {
            $icuRequest = $this->requests->create([...$validated, 'company_id' => $companyId, 'branch_id' => $branchId], $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        return ApiResponse::success($icuRequest, 'ICU admission request submitted.');
    }

    public function transfer(Request $request, IcuEpisode $episode)
    {
        $this->authorize('transfer', $episode);

        $validated = $request->validate(['destination' => ['required', 'string'], 'reason' => ['nullable', 'string']]);

        try {
            $episode = $this->transfers->completeTransfer($episode, $validated['destination'], $request->user(), $validated['reason'] ?? null);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        return ApiResponse::success($episode, 'ICU episode transferred.');
    }
}
