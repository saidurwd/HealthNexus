<?php

namespace App\Services\Laboratory;

use App\Events\Laboratory\LabReportFinalized;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabReportService
{
    public function __construct(
        private readonly LabNumberGenerator $numbers,
        private readonly LabOrderLifecycleService $lifecycle,
    ) {}

    public function finalize(LabOrder $order, User $user): LabReport
    {
        return DB::transaction(function () use ($order, $user) {
            if ($order->status !== 'validated') {
                throw ValidationException::withMessages(['order' => "Cannot finalize a report while the order is in '{$order->status}' status — every requested test must be validated first."]);
            }

            $report = LabReport::create([
                'company_id' => $order->company_id,
                'branch_id' => $order->branch_id,
                'lab_order_id' => $order->id,
                'report_number' => $this->numbers->generateReportNumber($order->company_id, $order->branch_id),
                'status' => 'final',
                'generated_by' => $user->id,
                'generated_at' => now(),
            ]);

            // Locks the results this report covers — results become editable only via
            // ResultAmendmentService's versioning from this point on, never a direct update.
            $order->items()->whereHas('results', fn ($q) => $q->where('is_current', true))
                ->get()
                ->each(function ($item) {
                    $item->currentResult()->update(['result_status' => 'reported', 'reported_at' => now()]);
                });

            $this->lifecycle->transitionTo($order, 'reported');

            event(new LabReportFinalized($report));

            return $report;
        });
    }

    public function cancel(LabReport $report, string $reason, User $user): LabReport
    {
        $report->update([
            'status' => 'cancelled',
            'cancelled_by' => $user->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $report->refresh();
    }
}
