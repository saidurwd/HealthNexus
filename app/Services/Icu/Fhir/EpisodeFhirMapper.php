<?php

namespace App\Services\Icu\Fhir;

use App\Models\Icu\IcuEpisode;

/**
 * FHIR Encounter-shaped array from an ICU episode (spec §64: ICU Episode -> Encounter).
 */
class EpisodeFhirMapper
{
    public function toEncounter(IcuEpisode $episode): array
    {
        return [
            'resourceType' => 'Encounter',
            'id' => (string) $episode->id,
            'status' => match ($episode->status) {
                IcuEpisode::STATUS_ADMITTED, IcuEpisode::STATUS_STEP_DOWN => 'in-progress',
                IcuEpisode::STATUS_TRANSFERRED, IcuEpisode::STATUS_DISCHARGED => 'finished',
                default => 'unknown',
            },
            'class' => [['code' => 'ICU', 'display' => 'Intensive Care Unit']],
            'subject' => ['reference' => "Patient/{$episode->patient_id}"],
            'partOf' => $episode->admission_id ? ['reference' => "Encounter/admission-{$episode->admission_id}"] : null,
            'participant' => array_filter([
                $episode->admitting_provider_id ? ['individual' => ['reference' => "Practitioner/{$episode->admitting_provider_id}"], 'type' => [['text' => 'admitting']]] : null,
                $episode->attending_provider_id ? ['individual' => ['reference' => "Practitioner/{$episode->attending_provider_id}"], 'type' => [['text' => 'attending']]] : null,
            ]),
            'period' => ['start' => $episode->admitted_at?->toAtomString(), 'end' => $episode->discharged_at?->toAtomString()],
            'location' => $episode->icu_bed_id ? [['location' => ['reference' => "Location/bed-{$episode->icu_bed_id}"]]] : [],
            'reasonCode' => $episode->admission_reason ? [['text' => $episode->admission_reason]] : [],
        ];
    }
}
