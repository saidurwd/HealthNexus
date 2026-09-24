<?php

namespace App\Services\Ipd;

use App\Events\Ipd\PatientAdmitted;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdAdmissionRequest;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedAllocation;
use App\Models\Ipd\IpdProviderAssignment;
use App\Models\Provider;
use App\Models\User;
use App\Services\EncounterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The Phase-8 analogue of PharmacyOrderService: turns an approved admission request (or a direct
 * admission) into a real IpdAdmission — reuses EncounterService::createEncounter() verbatim
 * (encounter_type='IPD') rather than duplicating encounter creation, generates the admission
 * number via the locked IpdNumberGenerator, optionally allocates the requested bed, records
 * initial provider assignments, and publishes the one-time admission-fee charge.
 */
class IpdAdmissionService
{
    public function __construct(
        private readonly EncounterService $encounters,
        private readonly IpdNumberGenerator $numbers,
        private readonly IpdBedAllocationService $allocation,
        private readonly IpdProviderAssignmentService $providerAssignment,
        private readonly IpdChargeService $charges,
    ) {}

    /**
     * @param  array{company_id:int,branch_id?:int|null,patient_id:int,department_id?:int|null,specialty_id?:int|null,admission_type_id?:int|null,admission_source_id?:int|null,admitting_provider_id?:int|null,attending_provider_id?:int|null,expected_discharge_date?:string|null,priority?:string|null,bed_id?:int|null}  $data
     */
    public function admit(array $data, User $user, ?IpdAdmissionRequest $request = null): IpdAdmission
    {
        return DB::transaction(function () use ($data, $user, $request) {
            if ($request) {
                if ($request->status !== IpdAdmissionRequest::STATUS_APPROVED) {
                    throw ValidationException::withMessages(['request' => "This request must be approved before admission (currently '{$request->status}')."]);
                }

                if ($request->admission()->exists()) {
                    throw ValidationException::withMessages(['request' => 'This request has already resulted in an admission.']);
                }
            }

            $companyId = $data['company_id'];
            $branchId = $data['branch_id'] ?? null;

            $attendingProvider = ! empty($data['attending_provider_id']) ? Provider::query()->findOrFail($data['attending_provider_id']) : null;
            $admittingProvider = ! empty($data['admitting_provider_id']) ? Provider::query()->findOrFail($data['admitting_provider_id']) : null;

            $encounter = $this->encounters->createEncounter([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'patient_id' => $data['patient_id'],
                'department_id' => $data['department_id'] ?? $request?->department_id,
                'encounter_type' => 'IPD',
                'provider_id' => $attendingProvider?->user_id,
            ], $user);

            $admission = IpdAdmission::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'admission_number' => $this->numbers->generateAdmissionNumber($companyId, $branchId),
                'patient_id' => $data['patient_id'],
                'admission_request_id' => $request?->id,
                'encounter_id' => $encounter->id,
                'admission_type_id' => $data['admission_type_id'] ?? $request?->admission_type_id,
                'admission_source_id' => $data['admission_source_id'] ?? $request?->admission_source_id,
                'department_id' => $data['department_id'] ?? $request?->department_id,
                'specialty_id' => $data['specialty_id'] ?? $request?->specialty_id,
                'admitted_at' => now(),
                'expected_discharge_date' => $data['expected_discharge_date'] ?? $request?->expected_discharge_date,
                'priority' => $data['priority'] ?? $request?->priority ?? 'routine',
                'status' => 'admitted',
                'created_by' => $user->id,
                'admitted_by' => $user->id,
                'approved_by' => $request?->approved_by,
            ]);

            if ($admittingProvider) {
                $this->providerAssignment->assign($admission, $admittingProvider, IpdProviderAssignment::ROLE_ADMITTING, $user);
            }

            if ($attendingProvider) {
                $this->providerAssignment->assign($admission, $attendingProvider, IpdProviderAssignment::ROLE_ATTENDING, $user);
            }

            if (! empty($data['bed_id'])) {
                $bed = IpdBed::query()->findOrFail($data['bed_id']);
                $this->allocation->allocate($admission->fresh(), $bed, $user, IpdBedAllocation::TYPE_ADMISSION);
            }

            $this->charges->chargeAdmissionFee($admission, $user);

            $admission = $admission->fresh(['encounter', 'patient', 'admittingProvider', 'attendingProvider', 'currentAllocation.bed']);

            event(new PatientAdmitted($admission));

            return $admission;
        });
    }
}
