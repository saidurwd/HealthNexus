<?php

namespace App\Services\Radiology;

use App\Models\Radiology\RadiologyProcedure;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CRUD for the procedure catalog. Soft-delete only — radiology_orders reference procedures
 * historically, mirrors LabTestService.
 */
class RadiologyProcedureService
{
    public function create(array $data, User $user): RadiologyProcedure
    {
        return DB::transaction(function () use ($data, $user) {
            $this->assertUniqueCode($data['company_id'], $data['branch_id'] ?? null, $data['code']);

            return RadiologyProcedure::create([
                ...$data,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });
    }

    public function update(RadiologyProcedure $procedure, array $data, User $user): RadiologyProcedure
    {
        return DB::transaction(function () use ($procedure, $data, $user) {
            if (isset($data['code']) && $data['code'] !== $procedure->code) {
                $this->assertUniqueCode($procedure->company_id, $procedure->branch_id, $data['code'], $procedure->id);
            }

            $procedure->update([...$data, 'updated_by' => $user->id]);

            return $procedure->refresh();
        });
    }

    public function deactivate(RadiologyProcedure $procedure, User $user): void
    {
        $procedure->update(['is_active' => false, 'updated_by' => $user->id]);
        $procedure->delete();
    }

    private function assertUniqueCode(int $companyId, ?int $branchId, string $code, ?int $exceptId = null): void
    {
        $exists = RadiologyProcedure::query()
            ->forTenant($companyId, $branchId)
            ->where('code', $code)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['code' => 'This procedure code is already in use.']);
        }
    }
}
