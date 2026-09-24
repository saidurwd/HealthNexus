<?php

namespace App\Services\Nursing;

use App\Models\Nursing\NursingEducation;
use App\Models\Nursing\NursingEpisode;
use App\Models\User;

class NursingEducationService
{
    public function record(NursingEpisode $episode, array $data, User $user): NursingEducation
    {
        return NursingEducation::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'patient_id' => $episode->patient_id,
            'provided_at' => $data['provided_at'] ?? now(),
            'provided_by' => $user->id,
        ]);
    }
}
