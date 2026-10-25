<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUDeviceInserted;
use App\Events\Icu\ICUDeviceRemoved;
use App\Models\Icu\IcuDevice;
use App\Models\Icu\IcuDeviceAssessment;
use App\Models\Icu\IcuEpisode;
use App\Models\User;

/**
 * Lines/tubes/drains tracking — mirrors NursingDeviceService exactly, ICU-scoped.
 */
class IcuDeviceService
{
    public function insert(IcuEpisode $episode, array $data, User $user): IcuDevice
    {
        $device = IcuDevice::create([
            ...$data,
            'organization_id' => $episode->organization_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'patient_id' => $episode->patient_id,
            'inserted_by' => $user->id,
            'status' => IcuDevice::STATUS_ACTIVE,
        ]);

        event(new ICUDeviceInserted($device));

        return $device;
    }

    public function assess(IcuDevice $device, array $data, User $user): IcuDeviceAssessment
    {
        $assessment = $device->assessments()->create([
            ...$data,
            'assessed_at' => $data['assessed_at'] ?? now(),
            'assessed_by' => $user->id,
        ]);

        $device->update(['last_assessment_at' => $assessment->assessed_at]);

        return $assessment;
    }

    public function remove(IcuDevice $device, User $user, ?string $complication = null): IcuDevice
    {
        $device->update([
            'status' => IcuDevice::STATUS_REMOVED,
            'removal_date' => now()->toDateString(),
            'removal_by' => $user->id,
            'complication' => $complication,
        ]);

        event(new ICUDeviceRemoved($device->fresh()));

        return $device->refresh();
    }
}
