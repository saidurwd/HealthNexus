<?php

namespace App\Services\Ipd;

use App\Models\Ipd\IpdBed;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CRUD for beds. New beds are always created Available. Soft-delete only — allocations/movements
 * reference beds historically. Bed status itself is never written here after creation — only
 * IpdBedStatusService may transition it.
 */
class IpdBedService
{
    public function create(array $data, User $user): IpdBed
    {
        return DB::transaction(function () use ($data) {
            $this->assertUniqueCode($data['company_id'], $data['bed_code']);

            return IpdBed::create([...$data, 'status' => IpdBed::STATUS_AVAILABLE]);
        });
    }

    public function update(IpdBed $bed, array $data, User $user): IpdBed
    {
        return DB::transaction(function () use ($bed, $data) {
            unset($data['status']);

            if (isset($data['bed_code']) && $data['bed_code'] !== $bed->bed_code) {
                $this->assertUniqueCode($bed->company_id, $data['bed_code'], $bed->id);
            }

            $bed->update($data);

            return $bed->refresh();
        });
    }

    public function deactivate(IpdBed $bed, User $user): void
    {
        $bed->update(['is_active' => false]);
        $bed->delete();
    }

    private function assertUniqueCode(int $companyId, string $bedCode, ?int $exceptId = null): void
    {
        $exists = IpdBed::query()
            ->where('company_id', $companyId)
            ->where('bed_code', $bedCode)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['bed_code' => 'This bed code is already in use.']);
        }
    }
}
