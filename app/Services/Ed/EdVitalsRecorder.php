<?php

namespace App\Services\Ed;

use App\Models\Ed\EdVisit;
use App\Models\User;
use App\Services\Clinical\Observations\ObservationData;
use App\Services\Clinical\Observations\ObservationService;
use Illuminate\Validation\ValidationException;

/** The one place ED vital signs are written: always through the Observation Service, never into an ED table. */
class EdVitalsRecorder
{
    /** form field => observation catalogue key */
    private const VITALS = [
        'temperature' => 'body_temperature', 'pulse' => 'heart_rate', 'respiratory_rate' => 'respiratory_rate',
        'oxygen_saturation' => 'oxygen_saturation', 'weight' => 'body_weight',
    ];

    public function __construct(private readonly ObservationService $observations) {}

    /**
     * @param  array<string, mixed>  $vitals
     * @return int|null id of the blood-pressure observation set, when one was written
     */
    public function record(EdVisit $visit, User $by, array $vitals): ?int
    {
        $base = fn (array $o) => new ObservationData(...array_merge([
            'organizationId' => $visit->organization_id, 'patientId' => $visit->patient_id, 'contextType' => 'ed', 'encounterId' => $visit->encounter_id,
            'contextId' => $visit->id, 'branchId' => $visit->branch_id, 'sourceModule' => 'ed',
        ], $o));

        $setId = null;
        $systolic = $vitals['systolic'] ?? null;
        $diastolic = $vitals['diastolic'] ?? null;

        if (filled($systolic) && filled($diastolic)) {
            $setId = $this->observations->recordPanel('blood_pressure', ['systolic_bp' => (float) $systolic, 'diastolic_bp' => (float) $diastolic], $base([]), $by)['set']->id;
        } elseif (filled($systolic) || filled($diastolic)) {
            throw ValidationException::withMessages(['vitals' => 'Blood pressure needs both the upper (systolic) and lower (diastolic) number.']);
        }

        foreach (self::VITALS as $field => $key) {
            if (filled($vitals[$field] ?? null)) {
                $this->observations->record($base(['key' => $key, 'valueNumber' => (float) $vitals[$field]]), $by);
            }
        }

        return $setId;
    }
}
