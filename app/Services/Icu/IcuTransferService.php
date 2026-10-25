<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUPatientTransferred;
use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuEpisodeEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * ICU owns critical-care handover; Phase 8 owns the actual hospital location/bed transfer
 * (spec §32) — this never creates/closes the underlying IpdAdmission itself, mirroring OT's
 * recovery-transfer decision #8 from Phase 10. The destination ward/bed assignment is recorded as
 * intent (destination string) for ward/admission staff to act on through Phase 8's own flow.
 */
class IcuTransferService
{
    public function __construct(
        private readonly IcuBedAllocationService $bedAllocation,
        private readonly IcuEpisodeLifecycleService $lifecycle,
    ) {}

    public function requestTransfer(IcuEpisode $episode, string $destination, User $user, ?string $reason = null): IcuEpisodeEvent
    {
        return IcuEpisodeEvent::create([
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'event_type' => IcuEpisodeEvent::TYPE_TRANSFER_REQUESTED,
            'destination' => $destination,
            'reason' => $reason,
            'created_by' => $user->id,
        ]);
    }

    public function completeTransfer(IcuEpisode $episode, string $destination, User $user, ?string $reason = null): IcuEpisode
    {
        return DB::transaction(function () use ($episode, $destination, $user, $reason) {
            $currentAllocation = $episode->admission?->currentAllocation()->first();

            if ($currentAllocation && $currentAllocation->bed_id === $episode->icu_bed_id) {
                $this->bedAllocation->release($currentAllocation, $user, $reason);
            }

            $toStatus = $destination === 'another_icu' ? IcuEpisode::STATUS_TRANSFERRED : IcuEpisode::STATUS_STEP_DOWN;
            $this->lifecycle->transitionTo($episode, $toStatus);

            IcuEpisodeEvent::create([
                'company_id' => $episode->company_id,
                'branch_id' => $episode->branch_id,
                'episode_id' => $episode->id,
                'event_type' => IcuEpisodeEvent::TYPE_TRANSFER_COMPLETED,
                'from_status' => IcuEpisode::STATUS_ADMITTED,
                'to_status' => $toStatus,
                'from_bed_id' => $episode->icu_bed_id,
                'destination' => $destination,
                'reason' => $reason,
                'created_by' => $user->id,
            ]);

            event(new ICUPatientTransferred($episode->fresh()));

            return $episode->fresh();
        });
    }
}
