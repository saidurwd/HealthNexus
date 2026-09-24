<?php

namespace App\Jobs\Ipd;

use App\Models\Ipd\IpdBed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Reports (never silently fixes) discrepancies between a bed's status column and whether it
 * genuinely has an active allocation (spec §62): a bed marked Occupied with no active allocation,
 * or a bed marked Available/Cleaning/etc. while an active allocation still exists. Automatic
 * repair would require explicit policy and audit, which is out of scope for this phase — this
 * job only surfaces the discrepancy via the log so an administrator can investigate.
 */
class ReconcileBedStates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $discrepancies = [];

        IpdBed::query()
            ->with('currentAllocation')
            ->where('is_active', true)
            ->each(function (IpdBed $bed) use (&$discrepancies) {
                $hasActiveAllocation = $bed->currentAllocation !== null;

                if ($bed->status === IpdBed::STATUS_OCCUPIED && ! $hasActiveAllocation) {
                    $discrepancies[] = "Bed {$bed->bed_code} (#{$bed->id}) is marked Occupied but has no active allocation.";
                } elseif ($bed->status !== IpdBed::STATUS_OCCUPIED && $hasActiveAllocation) {
                    $discrepancies[] = "Bed {$bed->bed_code} (#{$bed->id}) has an active allocation but is marked '{$bed->status}', not Occupied.";
                }
            });

        if (! empty($discrepancies)) {
            Log::channel('single')->warning('IPD bed state reconciliation found discrepancies.', ['discrepancies' => $discrepancies]);
        }
    }
}
