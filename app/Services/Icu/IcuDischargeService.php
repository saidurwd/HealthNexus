<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUDischarged;
use App\Events\Icu\ICUDischargeStarted;
use App\Models\Icu\IcuDischargeSummary;
use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuEpisodeEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IcuDischargeService
{
    public function __construct(
        private readonly IcuBedAllocationService $bedAllocation,
        private readonly IcuEpisodeLifecycleService $lifecycle,
    ) {}

    public function start(IcuEpisode $episode, array $data, User $user): IcuDischargeSummary
    {
        $summary = IcuDischargeSummary::create([
            ...$data,
            'organization_id' => $episode->organization_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'status' => IcuDischargeSummary::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);

        event(new ICUDischargeStarted($summary));

        return $summary;
    }

    public function update(IcuDischargeSummary $summary, array $data): IcuDischargeSummary
    {
        if ($summary->status === IcuDischargeSummary::STATUS_FINAL) {
            throw ValidationException::withMessages(['summary' => 'This discharge summary is already finalized and cannot be edited directly.']);
        }

        $summary->update($data);

        return $summary->refresh();
    }

    public function finalize(IcuDischargeSummary $summary, User $user): IcuDischargeSummary
    {
        return DB::transaction(function () use ($summary, $user) {
            if ($summary->status === IcuDischargeSummary::STATUS_FINAL) {
                throw ValidationException::withMessages(['summary' => 'This discharge summary is already finalized.']);
            }

            $episode = $summary->episode;
            $currentAllocation = $episode->admission?->currentAllocation()->first();

            if ($currentAllocation && $currentAllocation->bed_id === $episode->icu_bed_id) {
                $this->bedAllocation->release($currentAllocation, $user, 'ICU discharge');
            }

            $this->lifecycle->transitionTo($episode, IcuEpisode::STATUS_DISCHARGED);
            $episode->update(['discharged_at' => now(), 'completed_by' => $user->id, 'completed_at' => now()]);

            $summary->update([
                'status' => IcuDischargeSummary::STATUS_FINAL,
                'finalized_by' => $user->id,
                'finalized_at' => now(),
            ]);

            IcuEpisodeEvent::create([
                'organization_id' => $episode->organization_id,
                'branch_id' => $episode->branch_id,
                'episode_id' => $episode->id,
                'event_type' => $summary->destination === 'death' ? IcuEpisodeEvent::TYPE_DEATH : IcuEpisodeEvent::TYPE_DISCHARGED,
                'to_status' => IcuEpisode::STATUS_DISCHARGED,
                'destination' => $summary->destination,
                'created_by' => $user->id,
            ]);

            event(new ICUDischarged($episode->fresh()));

            return $summary->refresh();
        });
    }
}
