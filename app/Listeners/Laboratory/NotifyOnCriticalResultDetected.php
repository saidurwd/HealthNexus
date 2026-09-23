<?php

namespace App\Listeners\Laboratory;

use App\Events\Laboratory\CriticalResultDetected;
use App\Models\Laboratory\LabCriticalResultAlert;
use App\Services\NotificationService;
use App\Services\SettingsService;

/**
 * Result Entered -> Critical Value Detected -> Alert -> Clinician Notification (spec §14).
 * Creates the audit-trail alert row and notifies the ordering provider; acknowledgement is a
 * separate, distinct authorized action (CriticalResultService::acknowledge()).
 */
class NotifyOnCriticalResultDetected
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly SettingsService $settings,
    ) {}

    public function handle(CriticalResultDetected $event): void
    {
        $result = $event->result;
        $order = $result->orderItem->labOrder;
        $provider = $order->encounter?->provider ?? $order->orderedBy;

        $channels = $this->settings->get('laboratory.critical_notification_channels', ['database', 'mail']);
        $channels = is_array($channels) ? $channels : json_decode((string) $channels, true) ?? ['database'];

        LabCriticalResultAlert::create([
            'company_id' => $result->company_id,
            'branch_id' => $result->branch_id,
            'result_id' => $result->id,
            'detected_at' => now(),
            'notified_to' => $provider?->id,
            'notification_method' => implode(',', $channels),
        ]);

        if ($provider) {
            $this->notifications->send($provider, 'critical_result', [
                'test' => $result->test->name,
                'patient' => $order->patient?->full_name ?? $order->patient_id,
                'value' => $result->numeric_value ?? $result->qualitative_value ?? $result->text_value ?? '',
            ], $channels);
        }
    }
}
