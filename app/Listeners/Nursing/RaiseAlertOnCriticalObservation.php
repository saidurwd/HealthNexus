<?php

namespace App\Listeners\Nursing;

use App\Events\Nursing\CriticalObservationDetected;
use App\Models\Nursing\NursingAlert;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\Nursing\NursingAlertService;

class RaiseAlertOnCriticalObservation
{
    public function __construct(
        private readonly NursingAlertService $alerts,
        private readonly NotificationService $notifications,
    ) {}

    public function handle(CriticalObservationDetected $event): void
    {
        $observation = $event->observation;

        $alert = $this->alerts->raise(
            $observation,
            "{$observation->observation_type} value {$observation->value} is {$event->breach}",
            NursingAlert::SEVERITY_HIGH,
        );

        $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['charge_nurse', 'nursing_supervisor']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $observation->company_id))
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $this->notifications->send($recipients, 'nursing_critical_observation', [
            'observation_type' => $observation->observation_type,
            'patient' => $observation->patient?->full_name ?? (string) $observation->patient_id,
            'admission_number' => $observation->admission?->admission_number ?? '—',
            'breach' => $event->breach,
        ]);
    }
}
