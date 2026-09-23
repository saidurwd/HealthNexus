<?php

namespace App\Services\Pharmacy;

use App\Models\Pharmacy\PharmacyMedication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CRUD for the medication catalog. Soft-delete only — batches/orders/dispensing items reference
 * medications historically, mirrors RadiologyProcedureService.
 */
class PharmacyMedicationService
{
    public function create(array $data, User $user): PharmacyMedication
    {
        return DB::transaction(function () use ($data, $user) {
            $this->assertUniqueCode($data['company_id'], $data['branch_id'] ?? null, $data['code']);

            $ingredients = $data['ingredients'] ?? [];
            unset($data['ingredients']);

            $medication = PharmacyMedication::create([
                ...$data,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($ingredients as $ingredient) {
                $medication->ingredients()->create($ingredient);
            }

            return $medication;
        });
    }

    public function update(PharmacyMedication $medication, array $data, User $user): PharmacyMedication
    {
        return DB::transaction(function () use ($medication, $data, $user) {
            if (isset($data['code']) && $data['code'] !== $medication->code) {
                $this->assertUniqueCode($medication->company_id, $medication->branch_id, $data['code'], $medication->id);
            }

            $ingredients = $data['ingredients'] ?? null;
            unset($data['ingredients']);

            $medication->update([...$data, 'updated_by' => $user->id]);

            if ($ingredients !== null) {
                $medication->ingredients()->delete();
                foreach ($ingredients as $ingredient) {
                    $medication->ingredients()->create($ingredient);
                }
            }

            return $medication->refresh();
        });
    }

    public function deactivate(PharmacyMedication $medication, User $user): void
    {
        $medication->update(['is_active' => false, 'updated_by' => $user->id]);
        $medication->delete();
    }

    /**
     * Best-effort match of a free-text prescription medicine name against the catalog, used by
     * CreatePharmacyOrderOnPrescriptionIssued. Never throws — returns null when unmatched so the
     * order item is preserved flagged rather than dropped.
     */
    public function findByFreeTextName(int $companyId, ?int $branchId, string $medicineName): ?PharmacyMedication
    {
        $needle = trim($medicineName);

        if ($needle === '') {
            return null;
        }

        return PharmacyMedication::query()
            ->forTenant($companyId, $branchId)
            ->where('is_active', true)
            ->where(function ($q) use ($needle) {
                $q->where('name', 'like', "%{$needle}%")
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$needle}%"));
            })
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$needle])
            ->first();
    }

    private function assertUniqueCode(int $companyId, ?int $branchId, string $code, ?int $exceptId = null): void
    {
        $exists = PharmacyMedication::query()
            ->forTenant($companyId, $branchId)
            ->where('code', $code)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['code' => 'This medication code is already in use.']);
        }
    }
}
