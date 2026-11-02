<?php

namespace App\Services\Lab;

use App\Support\Dashboard\DashboardPeriod;
use App\Support\Dashboard\Days;
use App\Support\Dashboard\Spread;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The figures of the laboratory dashboard. Now: the work waiting at each stage, specimens, critical results nobody has acknowledged. In the period (and the period before): how many orders, how long from order to the pathologist's
 * approval (median and 90th percentile), the share of specimens rejected, and reports finished per day. A report is finished when the last result of its order is approved.
 */
class LabOverview
{
    private const OPEN = ['registered', 'awaiting_collection', 'collected', 'received', 'awaiting_validation'];

    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, DashboardPeriod $period, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();
        $today = Days::today($now);
        $orders = fn () => DB::table('lab_orders')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->whereNull('deleted_at')->whereNull('cancelled_at');
        $reported = fn () => DB::table('lab_orders as o')->join('lab_order_items as i', 'i.lab_order_id', '=', 'o.id')->join('lab_results as r', 'r.lab_order_item_id', '=', 'i.id')->where('o.organization_id', $organizationId)
            ->when($branchId, fn ($q) => $q->where('o.branch_id', $branchId))->where('o.status', 'reported')->whereNotNull('r.pathologist_approved_at')->groupBy('o.id', 'o.ordered_at')->selectRaw('o.ordered_at as ordered_at, max(r.pathologist_approved_at) as reported_at');
        $in = function (DashboardPeriod $p) use ($organizationId, $branchId, $orders, $reported) {
            $tat = $reported()->whereBetween('o.ordered_at', [$p->fromUtc(), $p->toUtc()])->get()->map(fn ($r) => Spread::minutesBetween($r->ordered_at, $r->reported_at));
            $specimens = DB::table('lab_specimens')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->whereBetween('created_at', [$p->fromUtc(), $p->toUtc()]);

            return ['orders' => (int) $orders()->whereBetween('ordered_at', [$p->fromUtc(), $p->toUtc()])->count(), 'tat' => Spread::of($tat),
                'rejected_percent' => ($n = (clone $specimens)->count()) > 0 ? round(100 * (clone $specimens)->whereNotNull('rejected_at')->count() / $n, 1) : null];
        };
        $doneOn = fn (CarbonImmutable $d) => (int) DB::query()->fromSub($reported(), 'x')->whereBetween('reported_at', Days::bounds($d))->count();
        $critical = DB::table('lab_critical_result_alerts')->where('organization_id', $organizationId)->whereNull('acknowledged_at');
        $oldest = (clone $critical)->min('detected_at');
        $stages = $orders()->whereIn('status', self::OPEN)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return [
            'to_work' => (int) $stages->sum(), 'awaiting_validation' => (int) ($stages['awaiting_validation'] ?? 0),
            'stages' => collect(['registered' => 'Registered', 'awaiting_collection' => 'Waiting for the sample', 'collected' => 'Sample taken', 'received' => 'In the laboratory', 'awaiting_validation' => 'Waiting to be checked'])->map(fn ($label, $k) => ['stage' => $label, 'n' => (int) ($stages[$k] ?? 0)])->values()->all(),
            'specimens_pending' => (int) DB::table('lab_specimens')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->whereIn('status', ['pending', 'collected', 'in_transit'])->count(),
            'critical_unacknowledged' => (int) (clone $critical)->count(), 'oldest_critical_minutes' => $oldest ? Spread::minutesBetween($oldest, $now) : null,
            'reported_today' => $doneOn($today), 'reported_usual' => round(collect(range(1, 4))->map(fn ($w) => $doneOn($today->subWeeks($w)))->avg(), 1),
            'current' => $in($period), 'previous' => $in($period->previous()),
            'per_day' => collect(Days::perDay($orders(), 'ordered_at', $period->from, $period->to))->map(fn ($n, $d) => ['day' => CarbonImmutable::parse($d)->format('d M'), 'orders' => (int) $n])->values()->all(),
        ];
    }
}
