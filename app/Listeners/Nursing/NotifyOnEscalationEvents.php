<?php

namespace App\Listeners\Nursing;

use App\Events\Nursing\NursingEscalationCreated;
use App\Events\Nursing\NursingEscalationResolved;
use App\Models\User;
use App\Services\NotificationService;

class NotifyOnEscalationEvents
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handleCreated(NursingEscalationCreated $event): void
    {
        $escalation = $event->escalation;

        $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['charge_nurse', 'doctor', 'nursing_supervisor']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $escalation->company_id))
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $this->notifications->send($recipients, 'nursing_escalation_created', [
            'concern' => $escalation->concern,
            'patient' => $escalation->patient?->full_name ?? (string) $escalation->patient_id,
            'admission_number' => $escalation->admission?->admission_number ?? '—',
            'severity' => $escalation->severity,
        ]);
    }

    public function handleResolved(NursingEscalationResolved $event): void
    {
        $escalation = $event->escalation;

        if (! $escalation->createdBy) {
            return;
        }

        $this->notifications->send(collect([$escalation->createdBy]), 'nursing_escalation_resolved', [
            'patient' => $escalation->patient?->full_name ?? (string) $escalation->patient_id,
            'admission_number' => $escalation->admission?->admission_number ?? '—',
        ]);
    }
}
