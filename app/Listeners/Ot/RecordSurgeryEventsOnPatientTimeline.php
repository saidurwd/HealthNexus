<?php

namespace App\Listeners\Ot;

use App\Events\Ot\RecoveryCompleted;
use App\Events\Ot\SurgeryCompleted;
use App\Events\Ot\SurgeryScheduled;
use App\Events\Ot\SurgeryStarted;
use App\Services\Patients\PatientTimelineService;

/**
 * Mirrors RecordNursingEventsOnPatientTimeline's multi-handler-method shape (spec §57).
 */
class RecordSurgeryEventsOnPatientTimeline
{
    public function __construct(private readonly PatientTimelineService $timeline) {}

    public function handleScheduled(SurgeryScheduled $event): void
    {
        $surgery = $event->surgery;

        if (! $surgery->patient) {
            return;
        }

        $this->timeline->record(
            $surgery->patient,
            'OT_SURGERY_SCHEDULED',
            "Surgery {$surgery->surgery_number} scheduled".($surgery->scheduled_start_at ? ' for '.$surgery->scheduled_start_at->toDateTimeString() : ''),
            $surgery,
        );
    }

    public function handleStarted(SurgeryStarted $event): void
    {
        $surgery = $event->surgery;

        if (! $surgery->patient) {
            return;
        }

        $this->timeline->record($surgery->patient, 'OT_SURGERY_STARTED', "Procedure started for surgery {$surgery->surgery_number}", $surgery);
    }

    public function handleCompleted(SurgeryCompleted $event): void
    {
        $surgery = $event->surgery;

        if (! $surgery->patient) {
            return;
        }

        $this->timeline->record($surgery->patient, 'OT_SURGERY_COMPLETED', "Procedure completed for surgery {$surgery->surgery_number}", $surgery);
    }

    public function handleRecoveryCompleted(RecoveryCompleted $event): void
    {
        $recovery = $event->recovery;

        if (! $recovery->patient) {
            return;
        }

        $this->timeline->record($recovery->patient, 'OT_RECOVERY_COMPLETED', 'Recovery/PACU completed, destination: '.($recovery->destination ?? 'unspecified'), $recovery);
    }
}
