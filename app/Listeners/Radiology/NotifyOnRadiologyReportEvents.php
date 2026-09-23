<?php

namespace App\Listeners\Radiology;

use App\Events\Radiology\RadiologyReportAmended;
use App\Events\Radiology\RadiologyReportFinalized;
use App\Services\NotificationService;

class NotifyOnRadiologyReportEvents
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handleFinalized(RadiologyReportFinalized $event): void
    {
        $order = $event->report->examination->orderItem->radiologyOrder;
        $provider = $order->encounter?->provider ?? $order->orderedBy;

        if (! $provider) {
            return;
        }

        $this->notifications->send($provider, 'radiology_report_ready', [
            'report_number' => $event->report->report_number,
            'patient' => $order->patient?->full_name ?? $order->patient_id,
        ], ['database', 'mail']);
    }

    public function handleAmended(RadiologyReportAmended $event): void
    {
        $order = $event->amendedReport->examination->orderItem->radiologyOrder;
        $provider = $order->encounter?->provider ?? $order->orderedBy;

        if (! $provider) {
            return;
        }

        $this->notifications->send($provider, 'radiology_report_amended', [
            'report_number' => $event->amendedReport->report_number,
            'patient' => $order->patient?->full_name ?? $order->patient_id,
            'reason' => $event->amendedReport->amendment_reason,
        ], ['database', 'mail']);
    }
}
