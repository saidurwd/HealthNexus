<?php

namespace App\Services\Nursing;

use App\Events\Nursing\CriticalObservationDetected;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingObservation;
use App\Models\User;
use App\Services\SettingsService;

/**
 * Append-only (spec §13/§57): a correction never updates the original row, it inserts a new one
 * with status=corrected referencing the row it corrects. Threshold breach only raises an event —
 * it never creates a diagnosis or takes any clinical action itself (spec §15).
 */
class NursingObservationService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function record(NursingEpisode $episode, array $data, User $user): NursingObservation
    {
        $observation = NursingObservation::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'patient_id' => $episode->patient_id,
            'encounter_id' => $episode->encounter_id,
            'status' => $data['status'] ?? NursingObservation::STATUS_FINAL,
            'observed_at' => $data['observed_at'] ?? now(),
            'observed_by' => $user->id,
        ]);

        $this->checkThreshold($observation);

        return $observation;
    }

    public function correct(NursingObservation $original, array $data, User $user): NursingObservation
    {
        $corrected = NursingObservation::create([
            ...$data,
            'company_id' => $original->company_id,
            'branch_id' => $original->branch_id,
            'episode_id' => $original->episode_id,
            'admission_id' => $original->admission_id,
            'patient_id' => $original->patient_id,
            'encounter_id' => $original->encounter_id,
            'observation_type' => $original->observation_type,
            'status' => NursingObservation::STATUS_CORRECTED,
            'observed_at' => $data['observed_at'] ?? $original->observed_at,
            'observed_by' => $user->id,
            'corrects_observation_id' => $original->id,
        ]);

        $this->checkThreshold($corrected);

        return $corrected;
    }

    private function checkThreshold(NursingObservation $observation): void
    {
        $thresholds = $this->settings->get('nursing.observation_thresholds', []);
        $range = $thresholds[$observation->observation_type] ?? null;

        if (! $range || ! is_numeric($observation->value)) {
            return;
        }

        $value = (float) $observation->value;
        $breach = null;

        if (isset($range['low']) && $range['low'] !== null && $value < (float) $range['low']) {
            $breach = 'below configured low threshold '.$range['low'];
        } elseif (isset($range['high']) && $range['high'] !== null && $value > (float) $range['high']) {
            $breach = 'above configured high threshold '.$range['high'];
        }

        if ($breach) {
            event(new CriticalObservationDetected($observation, $breach));
        }
    }
}
