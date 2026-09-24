<?php

namespace App\Services\Nursing;

use App\Events\Nursing\CarePlanCompleted;
use App\Events\Nursing\CarePlanCreated;
use App\Events\Nursing\CarePlanUpdated;
use App\Models\Nursing\NursingCarePlan;
use App\Models\Nursing\NursingCarePlanGoal;
use App\Models\Nursing\NursingCarePlanIntervention;
use App\Models\Nursing\NursingDiagnosis;
use App\Models\Nursing\NursingEpisode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class NursingCarePlanService
{
    public function create(NursingEpisode $episode, User $user): NursingCarePlan
    {
        $carePlan = NursingCarePlan::create([
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'patient_id' => $episode->patient_id,
            'status' => NursingCarePlan::STATUS_ACTIVE,
            'created_by' => $user->id,
        ]);

        event(new CarePlanCreated($carePlan));

        return $carePlan;
    }

    public function addDiagnosis(NursingCarePlan $carePlan, array $data, User $user): NursingDiagnosis
    {
        $diagnosis = $carePlan->diagnoses()->create([...$data, 'created_by' => $user->id]);

        event(new CarePlanUpdated($carePlan));

        return $diagnosis;
    }

    public function addGoal(NursingCarePlan $carePlan, array $data): NursingCarePlanGoal
    {
        $goal = $carePlan->goals()->create($data);

        event(new CarePlanUpdated($carePlan));

        return $goal;
    }

    public function addIntervention(NursingCarePlan $carePlan, array $data): NursingCarePlanIntervention
    {
        $intervention = $carePlan->interventions()->create($data);

        event(new CarePlanUpdated($carePlan));

        return $intervention;
    }

    public function evaluate(NursingCarePlanGoal $goal, string $status, ?string $evaluationNotes = null): NursingCarePlanGoal
    {
        $goal->update(['status' => $status]);

        if ($evaluationNotes !== null && $goal->carePlan) {
            event(new CarePlanUpdated($goal->carePlan));
        }

        return $goal->refresh();
    }

    public function complete(NursingCarePlan $carePlan): NursingCarePlan
    {
        $this->assertNotTerminal($carePlan);

        $carePlan->update(['status' => NursingCarePlan::STATUS_COMPLETED]);

        event(new CarePlanCompleted($carePlan));

        return $carePlan->refresh();
    }

    public function discontinue(NursingCarePlan $carePlan): NursingCarePlan
    {
        $this->assertNotTerminal($carePlan);

        $carePlan->update(['status' => NursingCarePlan::STATUS_DISCONTINUED]);

        return $carePlan->refresh();
    }

    private function assertNotTerminal(NursingCarePlan $carePlan): void
    {
        if (in_array($carePlan->status, [
            NursingCarePlan::STATUS_COMPLETED,
            NursingCarePlan::STATUS_DISCONTINUED,
            NursingCarePlan::STATUS_SUPERSEDED,
        ], true)) {
            throw ValidationException::withMessages(['care_plan' => "This care plan is already '{$carePlan->status}'."]);
        }
    }
}
