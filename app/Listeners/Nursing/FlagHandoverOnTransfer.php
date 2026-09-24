<?php

namespace App\Listeners\Nursing;

use App\Events\Ipd\PatientTransferred;
use App\Services\Nursing\NursingEpisodeService;
use App\Services\Nursing\NursingHandoverService;

/**
 * Marks the nursing episode 'transferred' on a ward/bed transfer and prepares a pending handover
 * (spec §50) — no separate ipd_transfers-shaped table here; the transfer nursing workflow is just
 * the same nursing_handover mechanism used for shift handover, triggered by Phase 8's event.
 * The receiving/incoming nurse is assigned later by NursingAssignmentService once known; this
 * listener only drafts the handover so it's ready for the receiving nurse to acknowledge.
 */
class FlagHandoverOnTransfer
{
    public function __construct(
        private readonly NursingEpisodeService $episodes,
        private readonly NursingHandoverService $handovers,
    ) {}

    public function handle(PatientTransferred $event): void
    {
        $episode = $this->episodes->markTransferred($event->movement);

        if ($episode && $episode->primary_nurse_id) {
            $this->handovers->prepare($episode, $episode->primaryNurse);
        }
    }
}
