<?php

namespace App\Listeners\Laboratory;

use App\Events\Laboratory\LabReportAmended;
use App\Events\Laboratory\LabReportFinalized;
use App\Services\Patients\PatientTimelineService;

/**
 * Mirrors App\Listeners\Clinical\RecordEncounterTimelineEvent — reuses the existing
 * PatientTimelineService rather than building a second timeline mechanism.
 */
class RecordLabReportOnPatientTimeline
{
    public function __construct(private readonly PatientTimelineService $timeline) {}

    public function handleFinalized(LabReportFinalized $event): void
    {
        $order = $event->report->labOrder;

        if (! $order->patient) {
            return;
        }

        $this->timeline->record(
            $order->patient,
            'LAB_REPORT_FINALIZED',
            "Lab report {$event->report->report_number} finalized for order {$order->order_number}",
            $event->report,
        );
    }

    public function handleAmended(LabReportAmended $event): void
    {
        $order = $event->report->labOrder;

        if (! $order->patient) {
            return;
        }

        $this->timeline->record(
            $order->patient,
            'LAB_REPORT_AMENDED',
            "Lab report {$event->report->report_number} amended: {$event->amendedResult->amendment_reason}",
            $event->report,
        );
    }
}
