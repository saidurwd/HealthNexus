<?php

namespace App\Services\Clinical;

use App\Models\ClinicalNumberCounter;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 * Row-locked counter, mirroring PatientNumberGenerator/AppointmentNumberGenerator — replaces the
 * unsafe orderByDesc('id')->id+1 pattern previously used by
 * EncounterClinicalService::createOrder() (clinical_orders.order_number) and
 * OpdService::createPrescription() (prescriptions.prescription_no).
 */
class ClinicalNumberGenerator
{
    public function generateOrderNumber(Company $company, ?int $branchId = null): string
    {
        $prefix = strtoupper(substr($company->code, 0, 3));
        $sequence = $this->nextSequence($company->id, $branchId, 'clinical_order');

        return sprintf('%s-ORD-%08d', $prefix, $sequence);
    }

    public function generatePrescriptionNumber(Company $company, ?int $branchId = null): string
    {
        $prefix = strtoupper(substr($company->code, 0, 3));
        $sequence = $this->nextSequence($company->id, $branchId, 'prescription');

        return sprintf('%s-PRX-%08d', $prefix, $sequence);
    }

    private function nextSequence(int $companyId, ?int $branchId, string $type): int
    {
        return DB::transaction(function () use ($companyId, $branchId, $type): int {
            $counter = ClinicalNumberCounter::query()
                ->where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->where('counter_type', $type)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = ClinicalNumberCounter::create([
                    'company_id' => $companyId,
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
