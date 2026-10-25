<?php

namespace App\Services\Icu;

use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuObservation;
use App\Models\User;

/**
 * Never updates an observation in place — a correction inserts a new row (corrects_observation_id
 * set), the old row untouched, mirroring NursingObservation's correction-chain convention.
 */
class IcuObservationService
{
    public function record(IcuEpisode $episode, array $data, User $user): IcuObservation
    {
        return IcuObservation::create([
            ...$data,
            'organization_id' => $episode->organization_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'patient_id' => $episode->patient_id,
            'encounter_id' => $episode->encounter_id,
            'observed_at' => $data['observed_at'] ?? now(),
            'recorded_by' => $user->id,
        ]);
    }

    public function correct(IcuObservation $observation, array $data, User $user): IcuObservation
    {
        return IcuObservation::create([
            ...$data,
            'organization_id' => $observation->organization_id,
            'branch_id' => $observation->branch_id,
            'episode_id' => $observation->episode_id,
            'patient_id' => $observation->patient_id,
            'encounter_id' => $observation->encounter_id,
            'observation_type' => $data['observation_type'] ?? $observation->observation_type,
            'observed_at' => $data['observed_at'] ?? $observation->observed_at,
            'recorded_by' => $user->id,
            'corrects_observation_id' => $observation->id,
        ]);
    }
}
