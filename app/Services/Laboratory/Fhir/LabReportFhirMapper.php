<?php

namespace App\Services\Laboratory\Fhir;

use App\Models\Laboratory\LabReport;

/**
 * Produces a FHIR-shaped DiagnosticReport array from an existing LabReport — readiness only,
 * per Phase 5 plan decision #8.
 */
class LabReportFhirMapper
{
    public function __construct(private readonly LabResultFhirMapper $resultMapper) {}

    public function toDiagnosticReport(LabReport $report): array
    {
        $order = $report->labOrder;

        $results = $order->items->flatMap(fn ($item) => $item->results()->where('is_current', true)->get());

        return [
            'resourceType' => 'DiagnosticReport',
            'id' => (string) $report->id,
            'status' => match ($report->status) {
                'preliminary' => 'preliminary',
                'final' => 'final',
                'amended' => 'amended',
                'cancelled' => 'cancelled',
                default => 'registered',
            },
            'category' => [[
                'coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/v2-0074',
                    'code' => 'LAB',
                ]],
            ]],
            'code' => [
                'text' => "Lab Order {$order->order_number}",
            ],
            'subject' => ['reference' => "Patient/{$order->patient_id}"],
            'encounter' => $order->encounter_id ? ['reference' => "Encounter/{$order->encounter_id}"] : null,
            'issued' => $report->generated_at?->toAtomString(),
            'identifier' => [[
                'system' => 'local-lab-report-number',
                'value' => $report->report_number,
            ]],
            'result' => $results->map(fn ($result) => ['reference' => "Observation/{$result->id}"])->values()->all(),
        ];
    }

    /**
     * Convenience: the full bundle of Observation resources backing this report.
     */
    public function observations(LabReport $report): array
    {
        $order = $report->labOrder;

        return $order->items
            ->flatMap(fn ($item) => $item->results()->where('is_current', true)->get())
            ->map(fn ($result) => $this->resultMapper->toObservation($result))
            ->values()
            ->all();
    }
}
