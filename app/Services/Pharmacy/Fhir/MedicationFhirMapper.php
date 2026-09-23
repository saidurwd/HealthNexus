<?php

namespace App\Services\Pharmacy\Fhir;

use App\Models\Pharmacy\PharmacyMedication;

/**
 * Produces a FHIR-shaped Medication array from an existing PharmacyMedication — readiness only.
 * No HL7 engine, no external calls; this is a pure mapper a future FHIR API surface could
 * serialize directly, mirroring LabResultFhirMapper.
 */
class MedicationFhirMapper
{
    public function toMedication(PharmacyMedication $medication): array
    {
        return [
            'resourceType' => 'Medication',
            'id' => (string) $medication->id,
            'code' => [
                'coding' => [[
                    'system' => 'local-pharmacy-medication-code',
                    'code' => $medication->code,
                    'display' => $medication->name,
                ]],
                'text' => $medication->name,
            ],
            'status' => $medication->is_active ? 'active' : 'inactive',
            'manufacturer' => $medication->manufacturer ? ['display' => $medication->manufacturer] : null,
            'form' => $medication->dosageForm ? [
                'coding' => [['code' => $medication->dosageForm->code, 'display' => $medication->dosageForm->name]],
            ] : null,
            'ingredient' => $medication->generic ? [[
                'itemCodeableConcept' => [
                    'coding' => [['system' => 'local-pharmacy-generic-code', 'display' => $medication->generic->generic_name]],
                ],
                'strength' => $medication->strength ? [
                    'numerator' => ['value' => $medication->strength, 'unit' => $medication->strength_unit],
                ] : null,
            ]] : [],
        ];
    }
}
