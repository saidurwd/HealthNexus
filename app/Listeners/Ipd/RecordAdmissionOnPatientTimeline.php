<?php

namespace App\Listeners\Ipd;

use App\Events\Ipd\PatientAdmitted;
use App\Events\Ipd\PatientDischarged;
use App\Events\Ipd\PatientTransferred;
use App\Services\Patients\PatientTimelineService;

class RecordAdmissionOnPatientTimeline
{
    public function __construct(private readonly PatientTimelineService $timeline) {}

    public function handleAdmitted(PatientAdmitted $event): void
    {
        $admission = $event->admission;

        if (! $admission->patient) {
            return;
        }

        $this->timeline->record(
            $admission->patient,
            'IPD_ADMISSION',
            "Admitted under {$admission->admission_number}",
            $admission,
        );
    }

    public function handleTransferred(PatientTransferred $event): void
    {
        $movement = $event->movement;

        if (! $movement->patient) {
            return;
        }

        $toBedCode = $movement->toBed?->bed_code ?? '—';

        $this->timeline->record(
            $movement->patient,
            'IPD_TRANSFER',
            "Transferred to bed {$toBedCode}",
            $movement,
        );
    }

    public function handleDischarged(PatientDischarged $event): void
    {
        $admission = $event->admission;

        if (! $admission->patient) {
            return;
        }

        $this->timeline->record(
            $admission->patient,
            'IPD_DISCHARGE',
            "Discharged from admission {$admission->admission_number}",
            $admission,
        );
    }
}
