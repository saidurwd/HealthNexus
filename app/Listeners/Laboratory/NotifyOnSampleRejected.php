<?php

namespace App\Listeners\Laboratory;

use App\Events\Laboratory\SampleRejected;
use App\Services\NotificationService;

/**
 * "The doctor/patient should be notified when recollection is required" (spec §22).
 */
class NotifyOnSampleRejected
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(SampleRejected $event): void
    {
        $order = $event->specimen->labOrder;
        $provider = $order->encounter?->provider ?? $order->orderedBy;

        if (! $provider) {
            return;
        }

        $this->notifications->send($provider, 'sample_rejected', [
            'accession_number' => $event->specimen->accession_number,
            'patient' => $order->patient?->full_name ?? $order->patient_id,
            'reason' => config('laboratory.rejection_reasons')[$event->specimen->rejection_reason] ?? $event->specimen->rejection_reason,
        ], ['database', 'mail']);
    }
}
