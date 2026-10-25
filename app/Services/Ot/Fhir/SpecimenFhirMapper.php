<?php

namespace App\Services\Ot\Fhir;

use App\Models\Ot\OtSpecimen;

class SpecimenFhirMapper
{
    public function toSpecimen(OtSpecimen $specimen): array
    {
        return [
            'resourceType' => 'Specimen',
            'id' => (string) $specimen->id,
            'identifier' => [['value' => $specimen->specimen_number]],
            'type' => ['text' => $specimen->specimen_type],
            'subject' => ['reference' => "Patient/{$specimen->patient_id}"],
            'collection' => [
                'collectedDateTime' => $specimen->collected_at?->toAtomString(),
                'collector' => $specimen->collected_by ? ['reference' => "Practitioner/{$specimen->collected_by}"] : null,
                'bodySite' => $specimen->site ? ['text' => $specimen->site] : null,
            ],
        ];
    }
}
