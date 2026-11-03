<?php

namespace App\Services\Dashboard;

use App\Support\Dashboard\Days;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * "Today in the hospital": the director's morning figures, worked out live for one hospital and one Dhaka day, each with the average of the same weekday over the four weeks before (so a Saturday is judged against Saturdays) and
 * a 14-day trend. Plain arrays, so it can be cached. These are counts and money, never patients.
 */
class TodayOverview
{
    private const WEEKS = 4;

    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?CarbonImmutable $now = null): array
    {
        $today = Days::today($now);
        $from = $today->subDays(13);
        [$start, $end] = Days::bounds($today);
        $o = fn (string $table) => DB::table($table)->where("$table.organization_id", $organizationId);
        $sameWeekday = fn (callable $count) => round(collect(range(1, self::WEEKS))->map(fn ($w) => (float) $count($today->subWeeks($w)))->avg(), 1);

        $opd = fn () => $o('encounters')->where('encounter_type', 'OPD')->whereNull('deleted_at')->where('status', '!=', 'cancelled');
        $invoices = fn () => $o('billing_invoices')->whereNotNull('finalized_at')->whereNotIn('status', ['draft', 'cancelled']);
        $payments = fn () => $o('billing_payments')->whereIn('status', ['completed', 'refunded'])->whereNotNull('invoice_id');

        $beds = (int) $o('ipd_beds')->where('is_active', true)->whereNull('deleted_at')->count();
        $occupied = (int) $o('ipd_bed_allocations')->where('status', 'active')->count();

        return [
            'date' => $today->toDateString(),
            'opd' => ['today' => (int) (clone $opd())->whereDate('encounter_date', $today->toDateString())->count(), 'usual' => $sameWeekday(fn ($d) => (clone $opd())->whereDate('encounter_date', $d->toDateString())->count()),
                'trend' => array_values(Days::perDay($opd(), 'encounter_date', $from, $today, 'count(*)', false))],
            'ed' => ['today' => (int) $o('ed_visits')->whereBetween('arrival_at', [$start, $end])->count(), 'usual' => $sameWeekday(fn ($d) => $o('ed_visits')->whereBetween('arrival_at', Days::bounds($d))->count()),
                'in_ed_now' => (int) $o('ed_visits')->where('status', '!=', 'dispositioned')->count(), 'trend' => array_values(Days::perDay($o('ed_visits'), 'arrival_at', $from, $today))],
            'ipd' => ['inpatients' => (int) $o('ipd_admissions')->where('status', 'admitted')->count(), 'beds' => $beds, 'occupied' => $occupied, 'occupancy' => $beds > 0 ? round(100 * $occupied / $beds, 1) : null,
                'admitted_today' => (int) $o('ipd_admissions')->whereBetween('admitted_at', [$start, $end])->count(), 'discharged_today' => (int) $o('ipd_admissions')->whereBetween('actual_discharge_date', [$start, $end])->count()],
            'theatre' => ['cases_today' => (int) $o('ot_surgeries')->whereDate('scheduled_date', $today->toDateString())->whereNotIn('status', ['cancelled', 'postponed'])->count(),
                'called_off_today' => (int) $o('ot_surgeries')->whereDate('scheduled_date', $today->toDateString())->whereIn('status', ['cancelled', 'postponed'])->count()],
            'billed' => ['today' => (float) (clone $invoices())->whereDate('invoice_date', $today->toDateString())->sum('grand_total'), 'usual' => $sameWeekday(fn ($d) => (clone $invoices())->whereDate('invoice_date', $d->toDateString())->sum('grand_total')),
                'trend' => array_values(Days::perDay($invoices(), 'invoice_date', $from, $today, 'sum(grand_total)', false))],
            'collected' => ['today' => (float) (clone $payments())->whereDate('payment_date', $today->toDateString())->sum('amount'), 'usual' => $sameWeekday(fn ($d) => (clone $payments())->whereDate('payment_date', $d->toDateString())->sum('amount')),
                'trend' => array_values(Days::perDay($payments(), 'payment_date', $from, $today, 'sum(amount)', false))],
            'alerts_open' => (int) $o('clinical_alerts')->where('status', 'open')->where('ack_required', true)->count(),
            'critical_open' => (int) $o('clinical_alerts')->where('status', 'open')->where('severity', 'critical')->where('ack_required', true)->count(),
            'approvals' => ['discounts' => (int) $o('billing_adjustments')->where('status', 'requested')->count(), 'grants' => DB::getSchemaBuilder()->hasTable('wf_grants') ? (int) $o('wf_grants')->where('status', 'requested')->count() : 0],
        ];
    }
}
