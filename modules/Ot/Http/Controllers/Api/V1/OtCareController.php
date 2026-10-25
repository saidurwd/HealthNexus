<?php

namespace Modules\Ot\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgeryRequest;
use App\Services\AuditLogger;
use App\Services\Ot\OtSchedulingService;
use App\Services\Ot\OtSurgeryRequestService;
use App\Services\Ot\OtSurgeryService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Every action route-model-binds the surgery/request and re-authorizes through its policy
 * (company/branch scope) — a client-supplied ID is never trusted (spec §65).
 */
class OtCareController extends Controller
{
    public function __construct(
        private readonly OtSurgeryRequestService $requests,
        private readonly OtSurgeryService $surgeries,
        private readonly OtSchedulingService $scheduling,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function dashboard(Request $request)
    {
        $this->authorize('viewAny', OtSurgery::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();
        $today = now()->toDateString();

        return ApiResponse::success([
            'scheduled_today' => OtSurgery::forTenant($companyId, $branchId)->whereDate('scheduled_date', $today)->count(),
            'in_procedure' => OtSurgery::forTenant($companyId, $branchId)->where('status', OtSurgery::STATUS_IN_PROCEDURE)->count(),
            'pending_requests' => OtSurgeryRequest::forTenant($companyId, $branchId)->whereIn('status', [OtSurgeryRequest::STATUS_REQUESTED, OtSurgeryRequest::STATUS_PENDING_APPROVAL])->count(),
        ]);
    }

    public function requests(Request $request)
    {
        $this->authorize('viewAny', OtSurgeryRequest::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        return ApiResponse::paginated(
            OtSurgeryRequest::forTenant($companyId, $branchId)->with('patient')->latest('requested_at')->paginate(20)
        );
    }

    public function storeRequest(Request $request)
    {
        $this->authorize('create', OtSurgeryRequest::class);

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'encounter_id' => ['required', 'integer', 'exists:encounters,id'],
            'procedure_id' => ['nullable', 'integer', 'exists:ot_procedures,id'],
            'indication' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
            'surgery_type' => ['nullable', 'string'],
        ]);

        $surgeryRequest = $this->requests->create($validated, $request->user());

        $this->auditLogger->log('CREATE', OtSurgeryRequest::class, $surgeryRequest->id, null, $surgeryRequest->toArray(), $request);

        return ApiResponse::success($surgeryRequest, 'Surgery request created.', 201);
    }

    public function approveRequest(Request $request, OtSurgeryRequest $surgeryRequest)
    {
        $this->authorize('approve', $surgeryRequest);

        try {
            $surgeryRequest = $this->requests->approve($surgeryRequest, $request->user(), $request->input('note'));
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        return ApiResponse::success($surgeryRequest, 'Surgery request approved.');
    }

    public function surgeries(Request $request)
    {
        $this->authorize('viewAny', OtSurgery::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        return ApiResponse::paginated(
            OtSurgery::forTenant($companyId, $branchId)
                ->with(['patient', 'theatre', 'primarySurgeon'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
                ->latest('scheduled_date')
                ->paginate(20)
        );
    }

    public function surgery(OtSurgery $surgery)
    {
        $this->authorize('view', $surgery);

        return ApiResponse::success($surgery->load(['patient', 'theatre', 'primarySurgeon', 'anesthetist', 'procedures']));
    }

    public function schedule(Request $request, OtSurgery $surgery)
    {
        $this->authorize('schedule', $surgery);

        $validated = $request->validate([
            'theatre_id' => ['required', 'integer', 'exists:ot_theatres,id'],
            'scheduled_start_at' => ['required', 'date'],
            'scheduled_end_at' => ['required', 'date', 'after:scheduled_start_at'],
            'primary_surgeon_id' => ['nullable', 'integer', 'exists:providers,id'],
            'anesthetist_id' => ['nullable', 'integer', 'exists:providers,id'],
        ]);

        $validated['scheduled_start_at'] = Carbon::parse($validated['scheduled_start_at']);
        $validated['scheduled_end_at'] = Carbon::parse($validated['scheduled_end_at']);

        try {
            $surgery = $this->scheduling->schedule($surgery, $validated, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('SCHEDULE', OtSurgery::class, $surgery->id, null, $validated, $request);

        return ApiResponse::success($surgery, 'Surgery scheduled.');
    }

    public function cancel(Request $request, OtSurgery $surgery)
    {
        $this->authorize('cancel', $surgery);

        $validated = $request->validate(['reason' => ['required', 'string']]);

        try {
            $surgery = $this->scheduling->cancel($surgery, $validated['reason'], $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        return ApiResponse::success($surgery, 'Surgery cancelled.');
    }
}
