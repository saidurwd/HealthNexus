<?php

namespace App\Listeners\Blood;

use App\Events\Laboratory\LabReportFinalized;
use App\Models\Blood\BloodDonation;
use App\Models\Laboratory\LabOrder;
use App\Services\Blood\BloodTestingService;

/** When the lab finalizes the report of a donation's tests, the results are read into the blood bank (no retyping). */
class SyncDonationTestsOnLabReport
{
    public function __construct(private readonly BloodTestingService $testing) {}

    public function handle(LabReportFinalized $event): void
    {
        $clinicalOrderId = LabOrder::query()->whereKey($event->report->lab_order_id)->value('clinical_order_id');
        if (! $clinicalOrderId) {
            return;
        }

        BloodDonation::query()->where('clinical_order_id', $clinicalOrderId)->each(fn (BloodDonation $donation) => $this->testing->syncFromLaboratory($donation));
    }
}
