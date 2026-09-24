<?php

namespace App\Services\Nursing;

use App\Models\Nursing\NursingDevice;
use App\Models\Nursing\NursingDeviceAssessment;
use App\Models\Nursing\NursingEpisode;
use App\Models\User;

class NursingDeviceService
{
    public function insert(NursingEpisode $episode, array $data, User $user): NursingDevice
    {
        return NursingDevice::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'patient_id' => $episode->patient_id,
            'inserted_by' => $user->id,
            'status' => NursingDevice::STATUS_ACTIVE,
        ]);
    }

    public function assess(NursingDevice $device, array $data, User $user): NursingDeviceAssessment
    {
        $assessment = $device->assessments()->create([
            ...$data,
            'assessed_at' => $data['assessed_at'] ?? now(),
            'assessed_by' => $user->id,
        ]);

        $device->update(['last_assessment_at' => $assessment->assessed_at]);

        return $assessment;
    }

    public function remove(NursingDevice $device, User $user, ?string $complication = null): NursingDevice
    {
        $device->update([
            'status' => NursingDevice::STATUS_REMOVED,
            'removal_date' => now()->toDateString(),
            'removal_by' => $user->id,
            'complication' => $complication,
        ]);

        return $device->refresh();
    }
}
