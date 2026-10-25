<?php

namespace App\Services\Icu;

use App\Models\Icu\IcuEpisode;
use Illuminate\Validation\ValidationException;

/**
 * The only place icu_episodes.status is ever written directly — mirrors
 * OtSurgeryLifecycleService/IpdAdmissionLifecycleService's const-table shape exactly, including
 * the same-status-is-idempotent-no-op convention.
 */
class IcuEpisodeLifecycleService
{
    private const TRANSITIONS = [
        IcuEpisode::STATUS_ADMITTED => [IcuEpisode::STATUS_STEP_DOWN, IcuEpisode::STATUS_TRANSFERRED, IcuEpisode::STATUS_DISCHARGED],
        IcuEpisode::STATUS_STEP_DOWN => [IcuEpisode::STATUS_ADMITTED, IcuEpisode::STATUS_TRANSFERRED, IcuEpisode::STATUS_DISCHARGED],
        IcuEpisode::STATUS_TRANSFERRED => [],
        IcuEpisode::STATUS_DISCHARGED => [],
    ];

    public function transitionTo(IcuEpisode $episode, string $status): IcuEpisode
    {
        if ($status === $episode->status) {
            return $episode;
        }

        if (! in_array($status, self::TRANSITIONS[$episode->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move ICU episode '{$episode->episode_number}' from '{$episode->status}' to '{$status}'.",
            ]);
        }

        $episode->update(['status' => $status]);

        return $episode->refresh();
    }
}
