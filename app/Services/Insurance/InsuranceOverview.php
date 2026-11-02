<?php

namespace App\Services\Insurance;

use App\Support\Dashboard\DashboardPeriod;
use App\Support\Dashboard\Spread;
use Illuminate\Support\Facades\DB;

/**
 * The period figures of the insurance dashboard (the standing counts - waiting pre-authorizations, deadlines, queries, aging - stay with InsuranceReportService). In the period (and the period before): claims sent and their value,
 * money the payers paid, how many claims were decided and the share denied or rejected, and the days from sending to the last payment (median and 90th percentile). Totals only.
 */
class InsuranceOverview
{
    private const FAILED = ['denied', 'rejected', 'written_off'];

    private const DECIDED = ['denied', 'rejected', 'paid', 'partially_paid', 'written_off', 'closed'];

    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, DashboardPeriod $period): array
    {
        $claims = fn () => DB::table('insurance_claims')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $in = function (DashboardPeriod $p) use ($claims) {
            $sent = fn () => $claims()->whereBetween('submitted_at', [$p->fromUtc(), $p->toUtc()]);
            $decided = (int) $sent()->whereIn('status', self::DECIDED)->count();
            $days = $sent()->where('status', 'paid')->get(['submitted_at', 'updated_at'])->map(fn ($c) => (int) round(Spread::minutesBetween($c->submitted_at, $c->updated_at) / 1440));

            return [
                'sent' => (int) $sent()->count(), 'sent_value' => round((float) $sent()->sum('payer_share'), 2), 'paid' => round((float) $sent()->sum('paid_amount'), 2),
                'denial_rate' => $decided > 0 ? round(100 * $sent()->whereIn('status', self::FAILED)->count() / $decided, 1) : null, 'decided' => $decided, 'days_to_payment' => Spread::of($days),
            ];
        };

        return ['current' => $in($period), 'previous' => $in($period->previous())];
    }
}
