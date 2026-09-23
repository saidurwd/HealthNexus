<?php

namespace App\Services\Patients;

use App\Models\Branch;
use App\Models\Company;
use App\Models\PatientNumberCounter;
use Illuminate\Support\Facades\DB;

class PatientNumberGenerator
{
    public function generateEnterprisePatientNo(Company $company): string
    {
        $prefix = strtoupper(substr($company->code, 0, 3));
        $sequence = $this->nextSequence($company->id, null, 'enterprise');

        return sprintf('%s-%08d', $prefix, $sequence);
    }

    public function generateLocalPatientNo(Company $company, Branch $branch): string
    {
        $prefix = strtoupper(substr($branch->code, 0, 3));
        $sequence = $this->nextSequence($company->id, $branch->id, 'local');

        return sprintf('%s-%08d', $prefix, $sequence);
    }

    /**
     * Row-locks (or creates then locks) the counter row for this company/branch/type inside a
     * transaction, so concurrent registrations serialize on the lock instead of racing on
     * "highest existing id + 1" — the previous approach, which could hand out the same number
     * twice under concurrent requests.
     */
    private function nextSequence(int $companyId, ?int $branchId, string $type): int
    {
        return DB::transaction(function () use ($companyId, $branchId, $type): int {
            $counter = PatientNumberCounter::query()
                ->where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->where('counter_type', $type)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = PatientNumberCounter::create([
                    'company_id' => $companyId,
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
