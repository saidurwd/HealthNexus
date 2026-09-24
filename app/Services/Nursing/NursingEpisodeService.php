<?php

namespace App\Services\Nursing;

use App\Events\Nursing\NursingEpisodeStarted;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBedMovement;
use App\Models\Nursing\NursingEpisode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Nursing episode lifecycle is driven by Phase 8's own admission events (decision #6 of the
 * Phase 9 plan) — location (ward/room/bed) is never denormalized here, it is always read live
 * through IpdAdmission::currentAllocation()->currentBed().
 */
class NursingEpisodeService
{
    public function startFromAdmission(IpdAdmission $admission, ?User $user = null): NursingEpisode
    {
        $existing = NursingEpisode::where('admission_id', $admission->id)->first();

        if ($existing) {
            return $existing;
        }

        $episode = NursingEpisode::create([
            'company_id' => $admission->company_id,
            'branch_id' => $admission->branch_id,
            'admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
            'encounter_id' => $admission->encounter_id,
            'status' => NursingEpisode::STATUS_ACTIVE,
            'start_at' => $admission->admitted_at ?? now(),
            'created_by' => $user?->id,
        ]);

        event(new NursingEpisodeStarted($episode));

        return $episode;
    }

    public function markTransferred(IpdBedMovement $movement): ?NursingEpisode
    {
        $episode = NursingEpisode::where('admission_id', $movement->admission_id)->first();

        if (! $episode || $episode->status !== NursingEpisode::STATUS_ACTIVE) {
            return $episode;
        }

        $episode->update(['status' => NursingEpisode::STATUS_TRANSFERRED]);

        return $episode->refresh();
    }

    public function reactivate(NursingEpisode $episode): NursingEpisode
    {
        if ($episode->status === NursingEpisode::STATUS_TRANSFERRED) {
            $episode->update(['status' => NursingEpisode::STATUS_ACTIVE]);
        }

        return $episode->refresh();
    }

    public function complete(IpdAdmission $admission): ?NursingEpisode
    {
        $episode = NursingEpisode::where('admission_id', $admission->id)->first();

        if (! $episode) {
            return null;
        }

        if (in_array($episode->status, [NursingEpisode::STATUS_COMPLETED, NursingEpisode::STATUS_CANCELLED], true)) {
            return $episode;
        }

        $episode->update(['status' => NursingEpisode::STATUS_COMPLETED, 'end_at' => now()]);

        return $episode->refresh();
    }

    public function cancel(NursingEpisode $episode): NursingEpisode
    {
        if (in_array($episode->status, [NursingEpisode::STATUS_COMPLETED, NursingEpisode::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages(['episode' => "This nursing episode is already '{$episode->status}'."]);
        }

        $episode->update(['status' => NursingEpisode::STATUS_CANCELLED, 'end_at' => now()]);

        return $episode->refresh();
    }
}
