<?php

namespace App\Services\Nursing;

use App\Models\Nursing\NursingEpisode;
use App\Models\User;
use App\Models\VitalSign;
use App\Services\Clinical\EncounterClinicalService;

/**
 * Thin wrapper — core vitals (temperature, pulse, RR, BP, SpO2, height, weight, BMI) are always
 * written through the existing VitalSign/EncounterClinicalService::addVital(), never a duplicate
 * nursing_vitals table (decision #1), so nursing and physician views share one trend history.
 */
class NursingVitalService
{
    public function __construct(private readonly EncounterClinicalService $clinical) {}

    public function record(NursingEpisode $episode, array $data, User $user): VitalSign
    {
        return $this->clinical->addVital($episode->encounter, $data, $user);
    }
}
