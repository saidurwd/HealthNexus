<?php

namespace App\Services\Laboratory\Fhir;

use App\Models\Laboratory\LabResult;

/**
 * Produces a FHIR-shaped Observation array from an existing LabResult — readiness only, per
 * Phase 5 plan decision #8. No HL7 engine, no external calls; this is a pure mapper a future
 * FHIR API surface could serialize directly.
 */
class LabResultFhirMapper
{
    public function toObservation(LabResult $result): array
    {
        $order = $result->orderItem->labOrder;

        return [
            'resourceType' => 'Observation',
            'id' => (string) $result->id,
            'status' => $this->fhirStatus($result->result_status),
            'category' => [[
                'coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                    'code' => 'laboratory',
                ]],
            ]],
            'code' => [
                'coding' => [[
                    'system' => 'local-lab-test-code',
                    'code' => $result->test->code,
                    'display' => $result->test->name,
                ]],
            ],
            'subject' => ['reference' => "Patient/{$order->patient_id}"],
            'encounter' => $order->encounter_id ? ['reference' => "Encounter/{$order->encounter_id}"] : null,
            'effectiveDateTime' => $result->entered_at?->toAtomString(),
            'issued' => $result->reported_at?->toAtomString(),
            'valueQuantity' => $result->numeric_value !== null ? [
                'value' => (float) $result->numeric_value,
                'unit' => $result->unit,
            ] : null,
            'valueString' => $result->numeric_value === null
                ? ($result->qualitative_value ?? $result->text_value)
                : null,
            'referenceRange' => ($result->reference_range_low !== null || $result->reference_range_high !== null) ? [[
                'low' => $result->reference_range_low !== null ? ['value' => (float) $result->reference_range_low] : null,
                'high' => $result->reference_range_high !== null ? ['value' => (float) $result->reference_range_high] : null,
            ]] : [],
            'interpretation' => $result->abnormal_flag ? [[
                'coding' => [['code' => $this->fhirInterpretationCode($result->abnormal_flag)]],
            ]] : [],
            'specimen' => $result->specimen_id ? ['reference' => "Specimen/{$result->specimen_id}"] : null,
            'performer' => $result->pathologist_approved_by ? [['reference' => "Practitioner/{$result->pathologist_approved_by}"]] : [],
        ];
    }

    private function fhirStatus(string $resultStatus): string
    {
        return match ($resultStatus) {
            'entered' => 'preliminary',
            'technically_validated', 'pathologist_validated' => 'preliminary',
            'reported' => 'final',
            'amended' => 'amended',
            'cancelled' => 'cancelled',
            default => 'registered',
        };
    }

    private function fhirInterpretationCode(string $abnormalFlag): string
    {
        return match ($abnormalFlag) {
            'low' => 'L',
            'high' => 'H',
            'critical_low' => 'LL',
            'critical_high' => 'HH',
            'positive' => 'POS',
            'negative' => 'NEG',
            'abnormal' => 'A',
            'normal' => 'N',
            default => 'IND',
        };
    }
}
