<?php

namespace App\Services\Patients;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\PatientNumberCounter;
use Illuminate\Support\Facades\DB;

class PatientNumberGenerator
{
    public function generateEnterprisePatientNo(Organization $organization): string
    {
        $prefix = strtoupper(substr($organization->code, 0, 3));
        $sequence = $this->nextSequence($organization->id, null, 'enterprise');

        return sprintf('%s-%08d', $prefix, $sequence);
    }

    public function generateLocalPatientNo(Organization $organization, Branch $branch): string
    {
        $prefix = strtoupper(substr($branch->code, 0, 3));
        $sequence = $this->nextSequence($organization->id, $branch->id, 'local');

        return sprintf('%s-%08d', $prefix, $sequence);
    }

    /**
     * Row-locks (or creates then locks) the counter row for this organization/branch/type inside a
     * transaction, so concurrent registrations serialize on the lock instead of racing on
     * "highest existing id + 1" — the previous approach, which could hand out the same number
     * twice under concurrent requests.
     */
    private function nextSequence(int $organizationId, ?int $branchId, string $type): int
    {
        return DB::transaction(function () use ($organizationId, $branchId, $type): int {
            $counter = PatientNumberCounter::query()
                ->where('organization_id', $organizationId)
                ->where('branch_id', $branchId)
                ->where('counter_type', $type)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = PatientNumberCounter::create([
                    'organization_id' => $organizationId,
                    'branch_id' => $branchId,
                    'counter_type' => $type,
                    'last_number' => 0,
                ]);

                $counter = PatientNumberCounter::query()
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
