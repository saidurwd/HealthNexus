<?php

namespace App\Services\Ipd;

use App\Models\Ipd\IpdAdmission;
use Illuminate\Validation\ValidationException;

/**
 * Single place that enforces the IpdAdmission status machine — no other service writes
 * IpdAdmission::status directly, mirrors PharmacyOrderLifecycleService.
 */
class IpdAdmissionLifecycleService
{
    private const TRANSITIONS = [
        'admitted' => ['active', 'transfer_requested', 'discharge_planned', 'deceased', 'lama', 'absconded', 'transferred_facility'],
        'active' => ['transfer_requested', 'transferred', 'discharge_planned', 'discharge_pending', 'deceased', 'lama', 'absconded', 'transferred_facility'],
        'transfer_requested' => ['transferred', 'active'],
        'transferred' => ['active', 'transfer_requested', 'discharge_planned', 'discharge_pending', 'deceased', 'lama', 'absconded', 'transferred_facility'],
        'discharge_planned' => ['discharge_pending', 'active'],
        'discharge_pending' => ['discharged', 'active'],
        'discharged' => ['closed'],
        'closed' => [],
        'cancelled' => [],
        'deceased' => ['closed'],
        'lama' => ['closed'],
        'absconded' => ['closed'],
        'transferred_facility' => ['closed'],
    ];

    public function transitionTo(IpdAdmission $admission, string $status): IpdAdmission
    {
        if ($status === $admission->status) {
            return $admission;
        }

        if (! in_array($status, self::TRANSITIONS[$admission->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move an admission from '{$admission->status}' to '{$status}'.",
            ]);
        }

        $admission->update(['status' => $status]);

        return $admission->refresh();
    }
}
