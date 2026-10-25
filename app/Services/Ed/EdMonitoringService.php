<?php

namespace App\Services\Ed;

use App\Models\Ed\EdVisit;
use App\Models\User;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use Illuminate\Validation\ValidationException;

/** Repeat observations while a patient is in the department. Values go to the Observation Service; limits come from config. */
class EdMonitoringService
{
    /** form field => deterioration key */
    private const FIELDS = [
        'pulse' => 'heart_rate', 'respiratory_rate' => 'respiratory_rate', 'oxygen_saturation' => 'oxygen_saturation',
        'temperature' => 'body_temperature', 'systolic' => 'systolic_bp',
    ];

    public function __construct(
        private readonly EdVitalsRecorder $vitals,
        private readonly AlertService $alerts,
        private readonly EdTriageService $triage,
    ) {}

    /**
     * @param  array<string, mixed>  $vitals
     * @return list<string> the readings that were outside the limits
     */
    public function record(EdVisit $visit, User $by, array $vitals): array
    {
        if (! $visit->isActive()) {
            throw ValidationException::withMessages(['visit' => 'This visit is closed.']);
        }
        if (! array_filter($vitals, 'filled')) {
            throw ValidationException::withMessages(['vitals' => 'Enter at least one measurement.']);
        }

        $this->vitals->record($visit, $by, $vitals);

        $breaches = $this->breaches($vitals);
        if ($breaches !== []) {
            $this->alerts->raise(new AlertRequest(
                organizationId: $visit->organization_id, category: 'ed_deterioration', severity: 'warning',
                message: "Possible deterioration — {$visit->visit_number}: ".implode(', ', $breaches),
                sourceModule: 'ed', patientId: $visit->patient_id, branchId: $visit->branch_id, sourceType: EdVisit::class, sourceId: $visit->id,
                recipientUserIds: $this->triage->roleUsers($visit->organization_id), dedupKey: "ed-deterioration:{$visit->id}:".now()->format('YmdHi'), raisedBy: $by->id,
            ));
        }

        return $breaches;
    }

    /** @return list<string> */
    public function breaches(array $vitals): array
    {
        $limits = config('ed.deterioration');
        $out = [];
        foreach (self::FIELDS as $field => $key) {
            if (! filled($vitals[$field] ?? null) || ! isset($limits[$key])) {
                continue;
            }
            [$low, $high] = $limits[$key];
            $value = (float) $vitals[$field];
            if (($low !== null && $value < $low) || ($high !== null && $value > $high)) {
                $out[] = "$field $value";
            }
        }

        return $out;
    }
}
