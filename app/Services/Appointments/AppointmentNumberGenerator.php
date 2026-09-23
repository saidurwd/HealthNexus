<?php

namespace App\Services\Appointments;

use App\Models\AppointmentNumberCounter;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 * Row-locked counter, mirroring App\Services\Patients\PatientNumberGenerator: reads/creates the
 * counter row inside a transaction, then re-selects it with lockForUpdate() before incrementing —
 * concurrent callers serialize on the row lock instead of racing on "highest id + 1", which is
 * what the previous AppointmentService::generateAppointmentNo()/generateTokenNo() did (both
 * caught in the Phase 2 gap audit as an exact regression of an already-fixed Phase 1 defect).
 */
class AppointmentNumberGenerator
{
    public function generateAppointmentNumber(Company $company): string
    {
        $prefix = strtoupper(substr($company->code, 0, 3));
        $sequence = $this->nextSequence($company->id, null, 'appointment');

        return sprintf('%s-APT-%08d', $prefix, $sequence);
    }

    public function generateTokenNumber(Company $company, ?Branch $branch): string
    {
        $prefix = strtoupper(substr($company->code, 0, 3)).strtoupper(substr($branch?->code ?? 'BR', 0, 2));
        $sequence = $this->nextSequence($company->id, $branch?->id, 'token');

        return sprintf('%s-%08d', $prefix, $sequence);
    }

    private function nextSequence(int $companyId, ?int $branchId, string $type): int
    {
        return DB::transaction(function () use ($companyId, $branchId, $type): int {
            $counter = AppointmentNumberCounter::query()
                ->where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->where('counter_type', $type)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = AppointmentNumberCounter::create([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'counter_type' => $type,
                    'last_number' => 0,
                ]);

                $counter = AppointmentNumberCounter::query()
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
