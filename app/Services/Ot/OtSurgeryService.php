<?php

namespace App\Services\Ot;

use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgeryRequest;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registers the surgical case itself — either from an approved OtSurgeryRequest or created
 * directly (walk-in OT booking), mirroring IpdAdmissionService::admit()'s dual entry points.
 */
class OtSurgeryService
{
    public function __construct(
        private readonly OtNumberGenerator $numbers,
        private readonly TenantContextResolver $tenant,
    ) {}

    public function createFromRequest(OtSurgeryRequest $request, User $user, array $overrides = []): OtSurgery
    {
        if ($request->status !== OtSurgeryRequest::STATUS_APPROVED) {
            throw ValidationException::withMessages(['request' => "This request must be approved before it can be scheduled as a surgical case (currently '{$request->status}')."]);
        }

        if ($request->surgery()->exists()) {
            throw ValidationException::withMessages(['request' => 'This request already has a surgical case.']);
        }

        return $this->create([
            'organization_id' => $request->organization_id,
            'branch_id' => $request->branch_id,
            'patient_id' => $request->patient_id,
            'admission_id' => $request->admission_id,
            'encounter_id' => $request->encounter_id,
            'surgery_request_id' => $request->id,
            'primary_procedure_id' => $request->procedure_id,
            'specialty_id' => $request->specialty_id,
            'priority' => $request->priority,
            'surgery_type' => $request->surgery_type,
            'site' => $request->site,
            'laterality' => $request->laterality,
            ...$overrides,
        ], $user);
    }

    public function create(array $data, User $user): OtSurgery
    {
        return DB::transaction(function () use ($data, $user) {
            $organizationId = $data['organization_id'] ?? $this->tenant->getOrganizationId();
            $branchId = $data['branch_id'] ?? $this->tenant->getBranchId();

            $surgery = OtSurgery::create([
                ...$data,
                'organization_id' => $organizationId,
                'branch_id' => $branchId,
                'surgery_number' => $this->numbers->generateSurgeryNumber($organizationId, $branchId),
                'status' => OtSurgery::STATUS_APPROVED,
                'created_by' => $user->id,
            ]);

            $surgery->procedures()->create([
                'procedure_id' => $surgery->primary_procedure_id,
                'role' => \App\Models\Ot\OtSurgeryProcedure::ROLE_PRIMARY,
                'sequence' => 1,
            ]);

            return $surgery->fresh();
        });
    }
}
