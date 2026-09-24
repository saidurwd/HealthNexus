<?php

namespace App\Services\Ipd;

use App\Models\Ipd\IpdWard;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CRUD for the ward catalog. Soft-delete only — rooms/admissions reference wards historically,
 * mirrors PharmacyMedicationService.
 */
class IpdWardService
{
    public function create(array $data, User $user): IpdWard
    {
        return DB::transaction(function () use ($data, $user) {
            $this->assertUniqueCode($data['company_id'], $data['branch_id'] ?? null, $data['code']);

            return IpdWard::create($data);
        });
    }

    public function update(IpdWard $ward, array $data, User $user): IpdWard
    {
        return DB::transaction(function () use ($ward, $data) {
            if (isset($data['code']) && $data['code'] !== $ward->code) {
                $this->assertUniqueCode($ward->company_id, $ward->branch_id, $data['code'], $ward->id);
            }

            $ward->update($data);

            return $ward->refresh();
        });
    }

    public function deactivate(IpdWard $ward, User $user): void
    {
        $ward->update(['is_active' => false]);
        $ward->delete();
    }

    private function assertUniqueCode(int $companyId, ?int $branchId, string $code, ?int $exceptId = null): void
    {
        $exists = IpdWard::query()
            ->forTenant($companyId, $branchId)
            ->where('code', $code)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['code' => 'This ward code is already in use.']);
        }
    }
}
