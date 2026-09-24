<?php

namespace App\Services\Nursing\Fhir;

use App\Models\Nursing\NursingObservation;

class ObservationFhirMapper
{
    public function toObservation(NursingObservation $observation): array
    {
        return [
            'resourceType' => 'Observation',
            'id' => (string) $observation->id,
            'status' => match ($observation->status) {
                NursingObservation::STATUS_PRELIMINARY => 'preliminary',
                NursingObservation::STATUS_CORRECTED => 'corrected',
                NursingObservation::STATUS_CANCELLED => 'cancelled',
                default => 'final',
            },
            'code' => ['text' => $observation->observation_type],
            'subject' => ['reference' => "Patient/{$observation->patient_id}"],
            'encounter' => ['reference' => "Encounter/{$observation->encounter_id}"],
            'effectiveDateTime' => $observation->observed_at?->toAtomString(),
            'valueString' => $observation->value.($observation->unit ? " {$observation->unit}" : ''),
        ];
    }
}
