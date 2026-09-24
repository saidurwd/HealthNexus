<?php

namespace App\Services\Nursing;

use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingRiskAssessment;
use App\Models\User;

class NursingRiskAssessmentService
{
    public function record(NursingEpisode $episode, array $data, User $user): NursingRiskAssessment
    {
        return NursingRiskAssessment::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'patient_id' => $episode->patient_id,
            'assessed_at' => $data['assessed_at'] ?? now(),
            'assessed_by' => $user->id,
        ]);
    }
}
