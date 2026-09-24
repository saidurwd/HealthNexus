<?php

namespace App\Services\Ipd\Fhir;

use App\Models\Ipd\IpdAdmission;

/**
 * Produces a FHIR-shaped Encounter array from an existing IpdAdmission — readiness only, per
 * spec §76. No FHIR server, no external calls; this is a pure mapper a future FHIR API surface
 * could serialize directly, mirroring Pharmacy's MedicationFhirMapper.
 */
class AdmissionFhirMapper
{
    public function toEncounter(IpdAdmission $admission): array
    {
        $bed = $admission->currentAllocation?->bed;

        return [
            'resourceType' => 'Encounter',
            'id' => (string) $admission->encounter_id,
            'status' => $this->fhirStatus($admission->status),
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'IMP',
                'display' => 'inpatient encounter',
            ],
            'subject' => ['reference' => "Patient/{$admission->patient_id}"],
            'participant' => array_filter([
                $admission->attending_provider_id ? [
                    'type' => [['text' => 'attending']],
                    'individual' => ['reference' => "Practitioner/{$admission->attending_provider_id}"],
                ] : null,
                $admission->admitting_provider_id ? [
                    'type' => [['text' => 'admitter']],
                    'individual' => ['reference' => "Practitioner/{$admission->admitting_provider_id}"],
                ] : null,
            ]),
            'period' => [
                'start' => $admission->admitted_at?->toAtomString(),
                'end' => $admission->actual_discharge_date?->toAtomString(),
            ],
            'hospitalization' => [
                'admitSource' => $admission->admissionSource ? ['text' => $admission->admissionSource->name] : null,
                'dischargeDisposition' => $admission->dischargeDisposition ? ['text' => $admission->dischargeDisposition->name] : null,
            ],
            'location' => $bed ? [[
                'location' => ['reference' => "Location/bed-{$bed->id}"],
                'status' => 'active',
            ]] : [],
            'identifier' => [[
                'system' => 'local-ipd-admission-number',
                'value' => $admission->admission_number,
            ]],
        ];
    }

    private function fhirStatus(string $status): string
    {
        return match ($status) {
            'admitted', 'active', 'transfer_requested', 'transferred', 'discharge_planned', 'discharge_pending' => 'in-progress',
            'discharged', 'closed' => 'finished',
            'deceased' => 'finished',
            'cancelled', 'lama', 'absconded', 'transferred_facility' => 'cancelled',
            default => 'unknown',
        };
    }
}
