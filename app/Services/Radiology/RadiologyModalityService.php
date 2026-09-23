<?php

namespace App\Services\Radiology;

use App\Models\Radiology\RadiologyModality;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RadiologyModalityService
{
    public function create(array $data): RadiologyModality
    {
        return DB::transaction(function () use ($data) {
            $this->assertUniqueCode($data['company_id'], $data['branch_id'] ?? null, $data['code']);

            return RadiologyModality::create($data);
        });
    }

    public function update(RadiologyModality $modality, array $data): RadiologyModality
    {
        return DB::transaction(function () use ($modality, $data) {
            if (isset($data['code']) && $data['code'] !== $modality->code) {
                $this->assertUniqueCode($modality->company_id, $modality->branch_id, $data['code'], $modality->id);
            }

            $modality->update($data);

            return $modality->refresh();
        });
    }

    public function setStatus(RadiologyModality $modality, string $status): RadiologyModality
    {
        if (! in_array($status, ['online', 'offline', 'maintenance', 'disabled'], true)) {
            throw ValidationException::withMessages(['status' => 'Invalid modality status.']);
        }

        $modality->update(['status' => $status]);

        return $modality->refresh();
    }

    private function assertUniqueCode(int $companyId, ?int $branchId, string $code, ?int $exceptId = null): void
    {
        $exists = RadiologyModality::query()
            ->forTenant($companyId, $branchId)
            ->where('code', $code)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['code' => 'This modality code is already in use.']);
        }
    }
}
