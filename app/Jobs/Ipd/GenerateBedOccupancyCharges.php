<?php

namespace App\Jobs\Ipd;

use App\Models\Ipd\IpdBedAllocation;
use App\Models\User;
use App\Services\Ipd\IpdChargeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Iterates active bed allocations and charges each admission for today's occupancy, idempotent
 * via ipd_bed_charge_events (unique per admission+date) — re-running this job for the same day
 * never double-charges. Skips admissions already discharged today or earlier (no bed-day charge
 * generated on/after actual_discharge_date), and skips patients currently on leave with the bed
 * released (no active allocation exists for them, so they're simply not in the query result).
 */
class GenerateBedOccupancyCharges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(IpdChargeService $charges): void
    {
        $today = now()->toDateString();
        $systemUser = User::first();

        if (! $systemUser) {
            return;
        }

        IpdBedAllocation::query()
            ->whereNull('released_at')
            ->with(['admission', 'bed'])
            ->each(function (IpdBedAllocation $allocation) use ($charges, $today, $systemUser) {
                $admission = $allocation->admission;

                if (! $admission || in_array($admission->status, ['discharged', 'closed'], true)) {
                    return;
                }

                if ($admission->actual_discharge_date && $admission->actual_discharge_date->toDateString() <= $today) {
                    return;
                }

                $charges->chargeBedDay($admission, $allocation->bed, $today, $systemUser);
            });
    }
}
