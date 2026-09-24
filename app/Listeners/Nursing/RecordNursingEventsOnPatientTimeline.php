<?php

namespace App\Listeners\Nursing;

use App\Events\Nursing\CarePlanCompleted;
use App\Events\Nursing\MedicationAdministrationRecorded;
use App\Events\Nursing\NursingDischargeChecklistCompleted;
use App\Events\Nursing\NursingEpisodeStarted;
use App\Events\Nursing\NursingEscalationCreated;
use App\Services\Patients\PatientTimelineService;

class RecordNursingEventsOnPatientTimeline
{
    public function __construct(private readonly PatientTimelineService $timeline) {}

    public function handleEpisodeStarted(NursingEpisodeStarted $event): void
    {
        $episode = $event->episode;

        if (! $episode->patient) {
            return;
        }

        $this->timeline->record(
            $episode->patient,
            'NURSING_EPISODE_STARTED',
            "Nursing care started for admission {$episode->admission?->admission_number}",
            $episode,
        );
    }

    public function handleMedicationAdministered(MedicationAdministrationRecorded $event): void
    {
        $mar = $event->administration;

        if (! $mar->patient) {
            return;
        }

        $this->timeline->record(
            $mar->patient,
            'NURSING_MEDICATION_ADMINISTERED',
            ($mar->medication?->name ?? 'Medication').' administered',
            $mar,
        );
    }

    public function handleCarePlanCompleted(CarePlanCompleted $event): void
    {
        $carePlan = $event->carePlan;

        if (! $carePlan->patient) {
            return;
        }

        $this->timeline->record($carePlan->patient, 'NURSING_CARE_PLAN_COMPLETED', 'Nursing care plan completed', $carePlan);
    }

    public function handleEscalationCreated(NursingEscalationCreated $event): void
    {
        $escalation = $event->escalation;

        if (! $escalation->patient) {
            return;
        }

        $this->timeline->record($escalation->patient, 'NURSING_ESCALATION', $escalation->concern, $escalation);
    }

    public function handleDischargeChecklistCompleted(NursingDischargeChecklistCompleted $event): void
    {
        $checklist = $event->checklist;

        if (! $checklist->patient) {
            return;
        }

        $this->timeline->record($checklist->patient, 'NURSING_DISCHARGE_CHECKLIST_COMPLETED', 'Nursing discharge checklist completed', $checklist);
    }
}
