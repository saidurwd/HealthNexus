<?php

namespace Modules\Nursing\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Nursing\NursingMedicationAdministration;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Nursing\MedicationAdministrationService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Every action route-model-binds the MAR row and re-authorizes through the policy (company/branch
 * scope) — a client-supplied ID is never trusted (spec §67).
 */
class NursingMarController extends Controller
{
    public function __construct(
        private readonly MedicationAdministrationService $mar,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', NursingMedicationAdministration::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $rows = NursingMedicationAdministration::forTenant($companyId, $branchId)
            ->with(['patient', 'medication'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('admission_id'), fn ($q) => $q->where('admission_id', $request->input('admission_id')))
            ->orderBy('scheduled_at')
            ->paginate(20);

        return ApiResponse::paginated($rows);
    }

    public function show(NursingMedicationAdministration $mar)
    {
        $this->authorize('view', $mar);

        return ApiResponse::success($mar->load(['patient', 'medication', 'batch', 'corrections']));
    }

    public function administer(Request $request, NursingMedicationAdministration $mar)
    {
        $this->authorize('administer', $mar);

        $validated = $request->validate([
            'dose' => ['nullable', 'string'], 'route' => ['nullable', 'string'], 'site' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'], 'witness_id' => ['nullable', 'integer', 'exists:users,id'],
            'safety_checks' => ['required', 'array'],
        ]);

        $witness = ! empty($validated['witness_id']) ? User::find($validated['witness_id']) : null;

        try {
            $updated = $this->mar->administer($mar, $request->user(), $validated['safety_checks'], $validated, $witness);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('ADMINISTER', NursingMedicationAdministration::class, $updated->id, null, ['status' => 'administered'], $request);

        return ApiResponse::success($updated, 'Medication administered.');
    }

    public function hold(Request $request, NursingMedicationAdministration $mar)
    {
        return $this->terminal($request, $mar, 'hold');
    }

    public function refuse(Request $request, NursingMedicationAdministration $mar)
    {
        return $this->terminal($request, $mar, 'refuse');
    }

    public function omit(Request $request, NursingMedicationAdministration $mar)
    {
        return $this->terminal($request, $mar, 'omit');
    }

    public function correct(Request $request, NursingMedicationAdministration $mar)
    {
        $this->authorize('correct', $mar);

        $validated = $request->validate(['correction_type' => ['nullable', 'string'], 'reason' => ['required', 'string']]);

        try {
            $correction = $this->mar->correct($mar, $validated, $validated['reason'], $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('CORRECT', NursingMedicationAdministration::class, $mar->id, null, $correction->toArray(), $request);

        return ApiResponse::success($correction, 'Correction recorded.');
    }

    private function terminal(Request $request, NursingMedicationAdministration $mar, string $action)
    {
        $this->authorize($action, $mar);

        $validated = $request->validate(['reason' => ['required', 'string']]);

        try {
            $updated = $this->mar->{$action}($mar, $request->user(), $validated['reason']);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log(strtoupper($action), NursingMedicationAdministration::class, $mar->id, null, $validated, $request);

        return ApiResponse::success($updated, 'Recorded.');
    }
}
