<?php

namespace App\Services\Icu\Fhir;

use App\Models\Icu\IcuObservation;

class ObservationFhirMapper
{
    public function toObservation(IcuObservation $observation): array
    {
        return [
            'resourceType' => 'Observation',
            'id' => (string) $observation->id,
            'status' => $observation->corrects_observation_id ? 'corrected' : 'final',
            'code' => ['text' => $observation->observation_type, 'coding' => $observation->code ? [['code' => $observation->code]] : []],
            'subject' => ['reference' => "Patient/{$observation->patient_id}"],
            'encounter' => ['reference' => "Encounter/{$observation->encounter_id}"],
            'effectiveDateTime' => $observation->observed_at?->toAtomString(),
            'valueQuantity' => $observation->value_numeric !== null ? ['value' => $observation->value_numeric, 'unit' => $observation->unit] : null,
            'valueString' => $observation->value_numeric === null ? $observation->value_text : null,
            'referenceRange' => ($observation->reference_range_low || $observation->reference_range_high) ? [[
                'low' => $observation->reference_range_low ? ['value' => $observation->reference_range_low] : null,
                'high' => $observation->reference_range_high ? ['value' => $observation->reference_range_high] : null,
            ]] : [],
            'device' => $observation->device_source ? ['display' => $observation->device_source] : null,
        ];
    }
}
