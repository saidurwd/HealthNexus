<?php

namespace App\Services\Icu;

use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuNutritionRecord;
use App\Models\User;

class IcuNutritionService
{
    public function record(IcuEpisode $episode, array $data, User $user): IcuNutritionRecord
    {
        return IcuNutritionRecord::create([
            ...$data,
            'organization_id' => $episode->organization_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'recorded_at' => $data['recorded_at'] ?? now(),
            'recorded_by' => $user->id,
        ]);
    }
}
