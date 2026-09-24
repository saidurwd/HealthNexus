<?php

namespace App\Listeners\Nursing;

use App\Events\Nursing\NurseAssigned;
use App\Services\NotificationService;

class NotifyOnAssignmentEvents
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(NurseAssigned $event): void
    {
        $assignment = $event->assignment;

        if (! $assignment->nurse) {
            return;
        }

        $this->notifications->send(collect([$assignment->nurse]), 'nursing_patient_assigned', [
            'patient' => $assignment->patient?->full_name ?? (string) $assignment->patient_id,
            'admission_number' => $assignment->admission?->admission_number ?? '—',
        ]);
    }
}
