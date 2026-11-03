<?php

namespace App\Services\Icu;

use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuFamilyCommunication;
use App\Models\User;

class IcuFamilyCommunicationService
{
    public function record(IcuEpisode $episode, array $data, User $user): IcuFamilyCommunication
    {
        return IcuFamilyCommunication::create([
            ...$data,
            'organization_id' => $episode->organization_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'communicated_at' => $data['communicated_at'] ?? now(),
            'documented_by' => $user->id,
        ]);
    }
}
