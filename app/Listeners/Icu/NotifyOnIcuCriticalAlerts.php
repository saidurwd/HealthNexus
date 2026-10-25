<?php

namespace App\Listeners\Icu;

use App\Events\Icu\ICUCodeBlueStarted;
use App\Events\Icu\ICUCriticalAlertTriggered;
use App\Models\Icu\IcuCriticalAlert;
use App\Models\Icu\IcuCriticalEvent;
use App\Models\User;
use App\Services\NotificationService;

class NotifyOnIcuCriticalAlerts
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handleAlertTriggered(ICUCriticalAlertTriggered $event): void
    {
        $alert = $event->alert;

        $this->notify($alert->episode->company_id, 'icu_critical_alert', [
            'patient' => $alert->episode->patient?->full_name ?? (string) $alert->episode->patient_id,
            'rule' => $alert->rule,
            'severity' => $alert->severity,
            'value' => $alert->triggered_value ?? '—',
        ]);
    }

    public function handleCodeBlueStarted(ICUCodeBlueStarted $event): void
    {
        /** @var IcuCriticalEvent $criticalEvent */
        $criticalEvent = $event->event;

        $this->notify($criticalEvent->company_id, 'icu_code_blue', [
            'patient' => $criticalEvent->episode->patient?->full_name ?? (string) $criticalEvent->episode->patient_id,
            'location' => $criticalEvent->location ?? 'ICU',
        ]);
    }

    private function notify(int $companyId, string $template, array $params): void
    {
        $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['icu_physician', 'intensivist', 'icu_charge_nurse']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $this->notifications->send($recipients, $template, $params);
    }
}
