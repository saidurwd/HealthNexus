<?php

namespace App\Services\Icu\Fhir;

use App\Models\Icu\IcuAssessment;

/**
 * FHIR ClinicalImpression-shaped array from a critical-care assessment (spec §64: ICU Clinical
 * Assessment -> ClinicalImpression).
 */
class AssessmentFhirMapper
{
    public function toClinicalImpression(IcuAssessment $assessment): array
    {
        return [
            'resourceType' => 'ClinicalImpression',
            'id' => (string) $assessment->id,
            'status' => $assessment->status === IcuAssessment::STATUS_FINAL ? 'completed' : 'in-progress',
            'subject' => ['reference' => "Patient/{$assessment->patient_id}"],
            'encounter' => ['reference' => "Encounter/episode-{$assessment->episode_id}"],
            'date' => $assessment->finalized_at?->toAtomString() ?? $assessment->created_at?->toAtomString(),
            'finding' => collect($assessment->sections ?? [])
                ->map(fn ($value, $section) => ['itemDetail' => [['text' => "{$section}: {$value}"]]])
                ->values()
                ->all(),
        ];
    }
}
