<?php

namespace App\Services\Ot\Fhir;

use App\Models\Ot\OtSurgery;

/**
 * FHIR Procedure-shaped array from a surgical case — covers both the scheduled and the actual
 * performed procedure (spec §82).
 */
class ProcedureFhirMapper
{
    public function toProcedure(OtSurgery $surgery): array
    {
        return [
            'resourceType' => 'Procedure',
            'id' => (string) $surgery->id,
            'status' => match ($surgery->status) {
                OtSurgery::STATUS_PROCEDURE_COMPLETED, OtSurgery::STATUS_RECOVERY, OtSurgery::STATUS_TRANSFERRED, OtSurgery::STATUS_CLOSED => 'completed',
                OtSurgery::STATUS_IN_PROCEDURE => 'in-progress',
                OtSurgery::STATUS_CANCELLED, OtSurgery::STATUS_REJECTED => 'not-done',
                OtSurgery::STATUS_ABORTED => 'stopped',
                default => 'preparation',
            },
            'subject' => ['reference' => "Patient/{$surgery->patient_id}"],
            'encounter' => ['reference' => "Encounter/{$surgery->encounter_id}"],
            'code' => ['text' => $surgery->primaryProcedure?->name],
            'performer' => array_filter([
                $surgery->primary_surgeon_id ? ['actor' => ['reference' => "Practitioner/{$surgery->primary_surgeon_id}"], 'function' => ['text' => 'primary surgeon']] : null,
                $surgery->anesthetist_id ? ['actor' => ['reference' => "Practitioner/{$surgery->anesthetist_id}"], 'function' => ['text' => 'anesthetist']] : null,
            ]),
            'location' => $surgery->theatre_id ? ['reference' => "Location/theatre-{$surgery->theatre_id}"] : null,
            'performedPeriod' => ['start' => $surgery->actual_start_at?->toAtomString(), 'end' => $surgery->actual_end_at?->toAtomString()],
            'bodySite' => $surgery->site ? [['text' => $surgery->site.($surgery->laterality ? " ({$surgery->laterality})" : '')]] : [],
        ];
    }
}
