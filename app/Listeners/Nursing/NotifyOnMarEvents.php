<?php

namespace App\Listeners\Nursing;

use App\Events\Nursing\MedicationOmitted;
use App\Events\Nursing\MedicationRefused;
use App\Models\User;
use App\Services\NotificationService;

class NotifyOnMarEvents
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handleRefused(MedicationRefused $event): void
    {
        $this->notifyPrescriber($event->administration, 'refused');
    }

    public function handleOmitted(MedicationOmitted $event): void
    {
        $this->notifyPrescriber($event->administration, 'omitted');
    }

    private function notifyPrescriber($mar, string $action): void
    {
        $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['charge_nurse', 'doctor']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $mar->company_id))
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $this->notifications->send($recipients, 'nursing_medication_overdue', [
            'medication' => $mar->medication?->name ?? 'Medication',
            'patient' => $mar->patient?->full_name ?? (string) $mar->patient_id,
            'admission_number' => $mar->admission?->admission_number ?? '—',
            'scheduled_at' => optional($mar->scheduled_at)->toDateTimeString() ?? '—',
        ]);
    }
}
