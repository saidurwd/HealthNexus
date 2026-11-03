<?php

namespace App\Services\Clinical;

use App\Models\ClinicalNumberCounter;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Row-locked counter, mirroring PatientNumberGenerator/AppointmentNumberGenerator — replaces the
 * unsafe orderByDesc('id')->id+1 pattern previously used by
 * EncounterClinicalService::createOrder() (clinical_orders.order_number) and
 * OpdService::createPrescription() (prescriptions.prescription_no).
 */
class ClinicalNumberGenerator
{
    public function generateOrderNumber(Organization $organization, ?int $branchId = null): string
    {
        $prefix = strtoupper(substr($organization->code, 0, 3));
        $sequence = $this->nextSequence($organization->id, $branchId, 'clinical_order');

        return sprintf('%s-ORD-%08d', $prefix, $sequence);
    }

    public function generatePrescriptionNumber(Organization $organization, ?int $branchId = null): string
    {
        $prefix = strtoupper(substr($organization->code, 0, 3));
        $sequence = $this->nextSequence($organization->id, $branchId, 'prescription');

        return sprintf('%s-PRX-%08d', $prefix, $sequence);
    }

    private function nextSequence(int $organizationId, ?int $branchId, string $type): int
    {
        return DB::transaction(function () use ($organizationId, $branchId, $type): int {
            $counter = ClinicalNumberCounter::query()
                ->where('organization_id', $organizationId)
                ->where('branch_id', $branchId)
                ->where('counter_type', $type)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = ClinicalNumberCounter::create([
                    'organization_id' => $organizationId,
                    'branch_id' => $branchId,
                    'counter_type' => $type,
                    'last_number' => 0,
                ]);

                $counter = ClinicalNumberCounter::query()
                    ->where('id', $counter->id)
                    ->lockForUpdate()
                    ->first();
            }

            $next = $counter->last_number + 1;
            $counter->update(['last_number' => $next]);

            return $next;
        });
    }
}
