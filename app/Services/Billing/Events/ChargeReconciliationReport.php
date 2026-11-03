<?php

namespace App\Services\Billing\Events;

use App\Models\Billing\BillingCharge;
use App\Models\Billing\ChargeEventLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/** Reconciliation (H-05): "events without charge" and "charges without event". */
class ChargeReconciliationReport
{
    /**
     * @return array{events_without_charge: Collection, charges_without_event: Collection}
     */
    public function run(int $organizationId, CarbonInterface $from, CarbonInterface $to): array
    {
        $eventsWithoutCharge = ChargeEventLog::query()
            ->where('organization_id', $organizationId)->whereBetween('created_at', [$from, $to])
            ->where(function ($q) {
                $q->where('outcome', ChargeEventLog::OUTCOME_REJECTED)
                    ->orWhere('outcome', ChargeEventLog::OUTCOME_REVERSAL_NEEDS_CREDIT_NOTE)
                    ->orWhere(fn ($q2) => $q2->where('outcome', ChargeEventLog::OUTCOME_ACCEPTED)->whereNull('billing_charge_id'));
            })->orderBy('id')->get();

        // Charges raised by a module's source record that the contract log never saw. Manual charges (no source) are expected.
        $chargesWithoutEvent = BillingCharge::query()
            ->where('organization_id', $organizationId)->whereBetween('created_at', [$from, $to])
            ->whereNotNull('source_type')
            ->whereNotExists(fn ($q) => $q->select(\DB::raw(1))->from('charge_event_log as l')->whereColumn('l.billing_charge_id', 'billing_charges.id'))
            ->orderBy('id')->get();

        return ['events_without_charge' => $eventsWithoutCharge, 'charges_without_event' => $chargesWithoutEvent];
    }
}
