<?php

namespace App\Services\Icu\Fhir;

use App\Models\Icu\IcuProcedureRecord;

class ProcedureFhirMapper
{
    public function toProcedure(IcuProcedureRecord $record): array
    {
        $record->loadMissing(['procedure', 'episode']);

        return [
            'resourceType' => 'Procedure',
            'id' => (string) $record->id,
            'status' => 'completed',
            'subject' => ['reference' => "Patient/{$record->episode?->patient_id}"],
            'encounter' => ['reference' => "Encounter/{$record->episode?->encounter_id}"],
            'code' => ['text' => $record->procedure?->name],
            'performer' => $record->performed_by ? [['actor' => ['reference' => "Practitioner/{$record->performed_by}"]]] : [],
            'performedDateTime' => $record->performed_at?->toAtomString(),
            'note' => $record->findings ? [['text' => $record->findings]] : [],
            'complication' => $record->complications ? [['text' => $record->complications]] : [],
        ];
    }
}
