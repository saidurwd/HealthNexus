<?php

namespace App\Services\Pharmacy;

use App\Services\SettingsService;
use App\Support\Dashboard\DashboardPeriod;
use App\Support\Dashboard\Days;
use App\Support\Dashboard\Spread;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The figures of the pharmacy dashboard. Now: prescriptions waiting, safety alerts nobody has acted on, batches near expiry that still have stock, quarantined batches, and medicines with nothing usable on the shelf (a batch that is
 * quarantined or past its date is not usable). In the period (and the period before): how many dispensings, how long from the order to the first dispensing (median and 90th percentile), dispensings per day, safety alerts by severity.
 */
class PharmacyOverview
{
    public function __construct(private readonly SettingsService $settings) {}

    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, DashboardPeriod $period, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();
        $today = Days::today($now);
        $near = $today->addDays((int) $this->settings->get('pharmacy.near_expiry_threshold_days', 90))->toDateString();
        $orders = fn () => DB::table('pharmacy_orders')->where('pharmacy_orders.organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('pharmacy_orders.branch_id', $branchId));
        $disp = fn () => DB::table('pharmacy_dispensings')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $usable = fn () => DB::table('pharmacy_stock as s')->join('pharmacy_batches as b', 'b.id', '=', 's.batch_id')->where('b.organization_id', $organizationId)->where('b.is_quarantined', false)->where('b.expiry_date', '>', $today->toDateString())->where('s.quantity_available', '>', 0);
        $in = function (DashboardPeriod $p) use ($orders, $disp) {
            $wait = $orders()->join('pharmacy_dispensings as d', 'd.order_id', '=', 'pharmacy_orders.id')->groupBy('pharmacy_orders.id', 'pharmacy_orders.ordered_at')->selectRaw('pharmacy_orders.ordered_at as ordered_at, min(d.dispensed_at) as first_at')
                ->havingRaw('min(d.dispensed_at) between ? and ?', [$p->fromUtc(), $p->toUtc()])->get()->map(fn ($r) => Spread::minutesBetween($r->ordered_at, $r->first_at));

            return ['dispensed' => (int) $disp()->whereBetween('dispensed_at', [$p->fromUtc(), $p->toUtc()])->count(), 'wait' => Spread::of($wait)];
        };
        $dispensedOn = fn (CarbonImmutable $d) => (int) $disp()->whereBetween('dispensed_at', Days::bounds($d))->count();
        $severity = DB::table('pharmacy_safety_alerts')->where('organization_id', $organizationId)->whereBetween('created_at', [$period->fromUtc(), $period->toUtc()])->selectRaw('severity, count(*) as n')->groupBy('severity')->pluck('n', 'severity');

        return [
            'orders_pending' => (int) $orders()->where('status', 'pending')->count(), 'orders_under_review' => (int) $orders()->where('status', 'under_review')->count(),
            'unresolved_safety_alerts' => (int) DB::table('pharmacy_safety_alerts')->where('organization_id', $organizationId)->where('is_overridden', false)->count(),
            'near_expiry_batches' => (int) DB::table('pharmacy_batches as b')->where('b.organization_id', $organizationId)->where('b.is_quarantined', false)->where('b.expiry_date', '<=', $near)->where('b.expiry_date', '>', $today->toDateString())
                ->whereExists(fn ($q) => $q->selectRaw('1')->from('pharmacy_stock as s')->whereColumn('s.batch_id', 'b.id')->where('s.quantity_available', '>', 0))->count(),
            'quarantined_batches' => (int) DB::table('pharmacy_batches')->where('organization_id', $organizationId)->where('is_quarantined', true)->count(),
            'stock_outs' => (int) DB::table('pharmacy_medications as m')->where('m.organization_id', $organizationId)->where('m.is_active', true)->whereNull('m.deleted_at')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('pharmacy_stock as s')->join('pharmacy_batches as b', 'b.id', '=', 's.batch_id')
                ->whereColumn('s.medication_id', 'm.id')->where('b.is_quarantined', false)->where('b.expiry_date', '>', $today->toDateString())->where('s.quantity_available', '>', 0))->count(),
            'dispensed_today' => $dispensedOn($today), 'dispensed_usual' => round(collect(range(1, 4))->map(fn ($w) => $dispensedOn($today->subWeeks($w)))->avg(), 1),
            'current' => $in($period), 'previous' => $in($period->previous()),
            'per_day' => collect(Days::perDay($disp(), 'dispensed_at', $period->from, $period->to))->map(fn ($n, $d) => ['day' => CarbonImmutable::parse($d)->format('d M'), 'dispensed' => (int) $n])->values()->all(),
            'alerts_by_severity' => $severity->map(fn ($n, $s) => ['severity' => ucfirst((string) $s), 'n' => (int) $n])->values()->all(),
            'near_days' => (int) $this->settings->get('pharmacy.near_expiry_threshold_days', 90),
        ];
    }
}
