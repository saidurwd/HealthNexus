<?php

namespace App\Listeners\Radiology;

use App\Events\Radiology\RadiologyReportAmended;
use App\Events\Radiology\RadiologyReportFinalized;
use App\Services\Patients\PatientTimelineService;

class RecordRadiologyReportOnPatientTimeline
{
    public function __construct(private readonly PatientTimelineService $timeline) {}

    public function handleFinalized(RadiologyReportFinalized $event): void
    {
        $order = $event->report->examination->orderItem->radiologyOrder;

        if (! $order->patient) {
            return;
        }

        $this->timeline->record(
            $order->patient,
            'RADIOLOGY_REPORT_FINALIZED',
            "Radiology report {$event->report->report_number} finalized for order {$order->order_number}",
            $event->report,
        );
    }

    public function handleAmended(RadiologyReportAmended $event): void
    {
        $order = $event->amendedReport->examination->orderItem->radiologyOrder;

        if (! $order->patient) {
            return;
        }

        $this->timeline->record(
            $order->patient,
            'RADIOLOGY_REPORT_AMENDED',
            "Radiology report {$event->amendedReport->report_number} amended: {$event->amendedReport->amendment_reason}",
            $event->amendedReport,
        );
    }
}
