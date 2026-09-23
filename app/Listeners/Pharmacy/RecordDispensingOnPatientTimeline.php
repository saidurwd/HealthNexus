<?php

namespace App\Listeners\Pharmacy;

use App\Events\Pharmacy\DispensingCompleted;
use App\Services\Patients\PatientTimelineService;

class RecordDispensingOnPatientTimeline
{
    public function __construct(private readonly PatientTimelineService $timeline) {}

    public function handle(DispensingCompleted $event): void
    {
        $dispensing = $event->dispensing;

        if (! $dispensing->patient) {
            return;
        }

        $this->timeline->record(
            $dispensing->patient,
            'PHARMACY_DISPENSING_COMPLETED',
            "Medications dispensed under {$dispensing->dispensing_number} ({$dispensing->status})",
            $dispensing,
        );
    }
}
