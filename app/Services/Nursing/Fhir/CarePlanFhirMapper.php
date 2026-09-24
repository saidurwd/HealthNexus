<?php

namespace App\Services\Nursing\Fhir;

use App\Models\Nursing\NursingCarePlan;

class CarePlanFhirMapper
{
    public function toCarePlan(NursingCarePlan $carePlan): array
    {
        return [
            'resourceType' => 'CarePlan',
            'id' => (string) $carePlan->id,
            'status' => match ($carePlan->status) {
                NursingCarePlan::STATUS_COMPLETED => 'completed',
                NursingCarePlan::STATUS_PAUSED => 'on-hold',
                NursingCarePlan::STATUS_DISCONTINUED, NursingCarePlan::STATUS_SUPERSEDED => 'revoked',
                default => 'active',
            },
            'intent' => 'plan',
            'subject' => ['reference' => "Patient/{$carePlan->patient_id}"],
            'goal' => $carePlan->goals->map(fn ($g) => ['display' => $g->goal_text])->all(),
            'activity' => $carePlan->interventions->map(fn ($i) => ['detail' => ['description' => $i->intervention_type]])->all(),
        ];
    }
}
