<?php

namespace App\Listeners\Laboratory;

use App\Events\Laboratory\LabReportAmended;
use App\Events\Laboratory\LabReportFinalized;
use App\Services\NotificationService;

class NotifyOnLabReportEvents
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handleFinalized(LabReportFinalized $event): void
    {
        $order = $event->report->labOrder;
        $provider = $order->encounter?->provider ?? $order->orderedBy;

        if (! $provider) {
            return;
        }

        $this->notifications->send($provider, 'report_ready', [
            'report_number' => $event->report->report_number,
            'patient' => $order->patient?->full_name ?? $order->patient_id,
        ], ['database', 'mail']);
    }

    public function handleAmended(LabReportAmended $event): void
    {
        $order = $event->report->labOrder;
        $provider = $order->encounter?->provider ?? $order->orderedBy;

        if (! $provider) {
            return;
        }

        $this->notifications->send($provider, 'report_amended', [
            'report_number' => $event->report->report_number,
            'patient' => $order->patient?->full_name ?? $order->patient_id,
            'reason' => $event->amendedResult->amendment_reason,
        ], ['database', 'mail']);
    }
}
