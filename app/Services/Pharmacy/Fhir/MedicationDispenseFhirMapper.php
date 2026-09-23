<?php

namespace App\Services\Pharmacy\Fhir;

use App\Models\Pharmacy\PharmacyDispensingItem;

/**
 * Produces a FHIR-shaped MedicationDispense array from an existing PharmacyDispensingItem —
 * readiness only, mirrors LabResultFhirMapper/MedicationFhirMapper. No HL7 engine, no external calls.
 */
class MedicationDispenseFhirMapper
{
    public function toMedicationDispense(PharmacyDispensingItem $item): array
    {
        $dispensing = $item->dispensing;

        return [
            'resourceType' => 'MedicationDispense',
            'id' => (string) $item->id,
            'status' => $dispensing->status,
            'medicationCodeableConcept' => [
                'coding' => [[
                    'system' => 'local-pharmacy-medication-code',
                    'code' => $item->medication->code,
                    'display' => $item->medication->name,
                ]],
            ],
            'subject' => ['reference' => "Patient/{$dispensing->patient_id}"],
            'performer' => $dispensing->dispensed_by ? [['actor' => ['reference' => "Practitioner/{$dispensing->dispensed_by}"]]] : [],
            'authorizingPrescription' => [['reference' => "MedicationRequest/{$dispensing->prescription_id}"]],
            'quantity' => ['value' => $item->quantity_dispensed, 'unit' => $item->unit],
            'whenHandedOver' => $dispensing->dispensed_at?->toAtomString(),
            'substitution' => $item->substitution_flag ? [
                'wasSubstituted' => true,
                'reason' => [['text' => $item->substitution_reason]],
            ] : ['wasSubstituted' => false],
        ];
    }
}
