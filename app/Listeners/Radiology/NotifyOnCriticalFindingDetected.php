<?php

namespace App\Listeners\Radiology;

use App\Events\Radiology\CriticalFindingDetected;
use App\Services\NotificationService;
use App\Services\SettingsService;

/**
 * Detection -> Notification (spec §46). Acknowledgement is a separate, distinct authorized
 * action (RadiologyCriticalFindingService::acknowledge()).
 */
class NotifyOnCriticalFindingDetected
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly SettingsService $settings,
    ) {}

    public function handle(CriticalFindingDetected $event): void
    {
        $finding = $event->finding;
        $examination = $finding->report->examination;
        $order = $examination->orderItem->radiologyOrder;
        $provider = $order->encounter?->provider ?? $order->orderedBy;

        if (! $provider) {
            return;
        }

        $channels = $this->settings->get('radiology.critical_finding_notification_channels', ['database', 'mail']);
        $channels = is_array($channels) ? $channels : (json_decode((string) $channels, true) ?? ['database']);

        $finding->update(['notified_to' => $provider->id, 'notification_method' => implode(',', $channels), 'notified_at' => now(), 'status' => 'notified']);

        $this->notifications->send($provider, 'radiology_critical_finding', [
            'procedure' => $examination->orderItem->procedure?->name ?? $examination->orderItem->requested_procedure_name,
            'patient' => $order->patient?->full_name ?? $order->patient_id,
            'finding' => $finding->finding_text,
        ], $channels);
    }
}
