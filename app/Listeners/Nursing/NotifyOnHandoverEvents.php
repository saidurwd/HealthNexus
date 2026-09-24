<?php

namespace App\Listeners\Nursing;

use App\Events\Nursing\NursingHandoverCreated;
use App\Services\NotificationService;

class NotifyOnHandoverEvents
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handleCreated(NursingHandoverCreated $event): void
    {
        $handover = $event->handover;

        if (! $handover->incomingNurse) {
            return;
        }

        $this->notifications->send(collect([$handover->incomingNurse]), 'nursing_handover_pending', [
            'patient' => $handover->episode?->patient?->full_name ?? '—',
            'admission_number' => $handover->admission?->admission_number ?? '—',
        ]);
    }
}
