<?php

namespace App\Listeners\Icu;

use App\Events\Icu\ICUDischarged;
use App\Events\Icu\ICUPatientAdmitted;
use App\Events\Icu\ICUPatientTransferred;
use App\Services\Patients\PatientTimelineService;

/**
 * Mirrors RecordSurgeryEventsOnPatientTimeline's multi-handler-method shape (spec §57).
 */
class RecordIcuEventsOnPatientTimeline
{
    public function __construct(private readonly PatientTimelineService $timeline) {}

    public function handleAdmitted(ICUPatientAdmitted $event): void
    {
        $episode = $event->episode;

        if (! $episode->patient) {
            return;
        }

        $this->timeline->record($episode->patient, 'ICU_ADMITTED', "Admitted to ICU, episode {$episode->episode_number}", $episode);
    }

    public function handleTransferred(ICUPatientTransferred $event): void
    {
        $episode = $event->episode;

        if (! $episode->patient) {
            return;
        }

        $this->timeline->record($episode->patient, 'ICU_TRANSFERRED', "ICU episode {$episode->episode_number} transferred, status {$episode->status}", $episode);
    }

    public function handleDischarged(ICUDischarged $event): void
    {
        $episode = $event->episode;

        if (! $episode->patient) {
            return;
        }

        $this->timeline->record($episode->patient, 'ICU_DISCHARGED', "Discharged from ICU, episode {$episode->episode_number}", $episode);
    }
}
