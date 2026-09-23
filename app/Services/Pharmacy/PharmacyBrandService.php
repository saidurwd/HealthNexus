<?php

namespace App\Services\Pharmacy;

use App\Models\Pharmacy\PharmacyBrand;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CRUD for the brand catalog. Soft-delete only — medications reference brands historically,
 * mirrors RadiologyProcedureService.
 */
class PharmacyBrandService
{
    public function create(array $data, User $user): PharmacyBrand
    {
        return DB::transaction(function () use ($data, $user) {
            $this->assertUniqueCode($data['company_id'], $data['branch_id'] ?? null, $data['code']);

            return PharmacyBrand::create([
                ...$data,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });
    }

    public function update(PharmacyBrand $brand, array $data, User $user): PharmacyBrand
    {
        return DB::transaction(function () use ($brand, $data, $user) {
            if (isset($data['code']) && $data['code'] !== $brand->code) {
                $this->assertUniqueCode($brand->company_id, $brand->branch_id, $data['code'], $brand->id);
            }

            $brand->update([...$data, 'updated_by' => $user->id]);

            return $brand->refresh();
        });
    }

    public function deactivate(PharmacyBrand $brand, User $user): void
    {
        $brand->update(['is_active' => false, 'updated_by' => $user->id]);
        $brand->delete();
    }

    private function assertUniqueCode(int $companyId, ?int $branchId, string $code, ?int $exceptId = null): void
    {
        $exists = PharmacyBrand::query()
            ->forTenant($companyId, $branchId)
            ->where('code', $code)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['code' => 'This brand code is already in use.']);
        }
    }
}
