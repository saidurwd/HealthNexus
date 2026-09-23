<?php

namespace App\Listeners\Clinical;

use App\Events\Clinical\DiagnosisAdded;
use App\Events\Clinical\EncounterCompleted;
use App\Events\Clinical\EncounterCreated;
use App\Events\Clinical\PrescriptionIssued;
use App\Events\Clinical\ReferralCreated;
use App\Services\Patients\PatientTimelineService;

/**
 * Populates the Patient Timeline (built in the Patient Core phase, event-sourced) from Clinical
 * events — the timeline previously never learned about anything happening in an encounter.
 */
class RecordEncounterTimelineEvent
{
    public function __construct(private PatientTimelineService $timeline) {}

    public function handleEncounterCreated(EncounterCreated $event): void
    {
        $encounter = $event->encounter;

        if (! $encounter->patient) {
            return;
        }

        $this->timeline->record(
            $encounter->patient,
            'ENCOUNTER_CREATED',
            "Encounter {$encounter->encounter_no} created",
            $encounter,
        );
    }

    public function handleEncounterCompleted(EncounterCompleted $event): void
    {
        $encounter = $event->encounter;

        if (! $encounter->patient) {
            return;
        }

        $this->timeline->record(
            $encounter->patient,
            'ENCOUNTER_COMPLETED',
            "Encounter {$encounter->encounter_no} completed",
            $encounter,
        );
    }

    public function handleDiagnosisAdded(DiagnosisAdded $event): void
    {
        $encounter = $event->encounter;

        if (! $encounter->patient) {
            return;
        }

        $this->timeline->record(
            $encounter->patient,
            'DIAGNOSIS_ADDED',
            "Diagnosis added: {$event->diagnosis->description}",
            $event->diagnosis,
        );
    }

    public function handlePrescriptionIssued(PrescriptionIssued $event): void
    {
        $encounter = $event->encounter;

        if (! $encounter->patient) {
            return;
        }

        $this->timeline->record(
            $encounter->patient,
            'PRESCRIPTION_ISSUED',
            "Prescription {$event->prescription->prescription_no} issued",
            $event->prescription,
        );
    }

    public function handleReferralCreated(ReferralCreated $event): void
    {
        $encounter = $event->encounter;

        if (! $encounter->patient) {
            return;
        }

        $this->timeline->record(
            $encounter->patient,
            'REFERRAL_CREATED',
            "Referred to {$event->referral->referred_to}",
            $event->referral,
        );
    }
}
