<?php

namespace App\Services\Pharmacy;

use App\Models\Pharmacy\PharmacyGeneric;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CRUD for the generic-drug catalog. Soft-delete only — medications reference generics
 * historically, mirrors RadiologyProcedureService.
 */
class PharmacyGenericService
{
    public function create(array $data, User $user): PharmacyGeneric
    {
        return DB::transaction(function () use ($data, $user) {
            $this->assertUniqueCode($data['company_id'], $data['branch_id'] ?? null, $data['code']);

            return PharmacyGeneric::create([
                ...$data,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });
    }

    public function update(PharmacyGeneric $generic, array $data, User $user): PharmacyGeneric
    {
        return DB::transaction(function () use ($generic, $data, $user) {
            if (isset($data['code']) && $data['code'] !== $generic->code) {
                $this->assertUniqueCode($generic->company_id, $generic->branch_id, $data['code'], $generic->id);
            }

            $generic->update([...$data, 'updated_by' => $user->id]);

            return $generic->refresh();
        });
    }

    public function deactivate(PharmacyGeneric $generic, User $user): void
    {
        $generic->update(['is_active' => false, 'updated_by' => $user->id]);
        $generic->delete();
    }

    private function assertUniqueCode(int $companyId, ?int $branchId, string $code, ?int $exceptId = null): void
    {
        $exists = PharmacyGeneric::query()
            ->forTenant($companyId, $branchId)
            ->where('code', $code)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['code' => 'This generic code is already in use.']);
        }
    }
}
