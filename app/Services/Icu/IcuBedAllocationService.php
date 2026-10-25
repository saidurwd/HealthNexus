<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUBedAssigned;
use App\Events\Icu\ICUBedChanged;
use App\Models\Icu\IcuEpisode;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedAllocation;
use App\Models\User;
use App\Services\Ipd\IpdBedAllocationService;
use Illuminate\Validation\ValidationException;

/**
 * Phase 8 remains the single bed-location source of truth (spec §5) — this is a thin wrapper
 * around IpdBedAllocationService, not a second allocation ledger. Reusing its exact
 * lockForUpdate()-on-the-bed-row mechanism already guarantees two ICU coordinators cannot assign
 * the same bed simultaneously; no new locking primitive is needed here.
 */
class IcuBedAllocationService
{
    public function __construct(private readonly IpdBedAllocationService $allocation) {}

    public function assign(IpdAdmission $admission, IpdBed $bed, User $user, ?string $reason = null): IpdBedAllocation
    {
        if (! $bed->icu_capable) {
            throw ValidationException::withMessages(['bed' => "Bed '{$bed->bed_code}' is not ICU-capable."]);
        }

        $current = $admission->currentAllocation;

        if ($current && $current->bed_id === $bed->id) {
            return $current;
        }

        if ($current) {
            $this->allocation->release($current, $user, $reason);
        }

        return $this->allocation->allocate($admission->fresh(), $bed, $user, IpdBedAllocation::TYPE_TRANSFER, $reason);
    }

    public function release(IpdBedAllocation $allocation, User $user, ?string $reason = null): IpdBedAllocation
    {
        return $this->allocation->release($allocation, $user, $reason);
    }

    public function changeBed(IcuEpisode $episode, IpdBed $newBed, User $user, ?string $reason = null): IpdBedAllocation
    {
        if (! $episode->admission) {
            throw ValidationException::withMessages(['episode' => 'This ICU episode has no linked admission to reallocate a bed for.']);
        }

        $oldBedId = $episode->icu_bed_id;
        $allocation = $this->assign($episode->admission, $newBed, $user, $reason);

        $episode->update(['icu_unit_id' => $newBed->room?->ward_id, 'icu_bed_id' => $newBed->id]);

        event($oldBedId ? new ICUBedChanged($episode->fresh()) : new ICUBedAssigned($episode->fresh()));

        return $allocation;
    }
}
