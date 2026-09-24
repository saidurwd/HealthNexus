<?php

namespace App\Services\Nursing\Fhir;

use App\Models\Nursing\NursingMedicationAdministration;

/**
 * FHIR MedicationAdministration-shaped array from a MAR row — readiness only, no FHIR server.
 */
class MedicationAdministrationFhirMapper
{
    public function toMedicationAdministration(NursingMedicationAdministration $mar): array
    {
        return [
            'resourceType' => 'MedicationAdministration',
            'id' => (string) $mar->id,
            'status' => match ($mar->status) {
                NursingMedicationAdministration::STATUS_ADMINISTERED => 'completed',
                NursingMedicationAdministration::STATUS_HELD => 'on-hold',
                NursingMedicationAdministration::STATUS_CANCELLED => 'stopped',
                NursingMedicationAdministration::STATUS_REFUSED, NursingMedicationAdministration::STATUS_OMITTED => 'not-done',
                default => 'in-progress',
            },
            'subject' => ['reference' => "Patient/{$mar->patient_id}"],
            'context' => ['reference' => "Encounter/{$mar->encounter_id}"],
            'medicationCodeableConcept' => ['text' => $mar->medication?->name],
            'effectiveDateTime' => $mar->administered_at?->toAtomString(),
            'performer' => $mar->administered_by ? [['actor' => ['reference' => "Practitioner/{$mar->administered_by}"]]] : [],
            'dosage' => ['text' => trim("{$mar->dose} {$mar->dose_unit}"), 'route' => ['text' => $mar->route], 'site' => ['text' => $mar->site]],
            'statusReason' => $mar->reason_if_not_administered ? [['text' => $mar->reason_if_not_administered]] : [],
        ];
    }
}
