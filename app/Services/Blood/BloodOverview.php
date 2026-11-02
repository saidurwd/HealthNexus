<?php

namespace App\Services\Blood;

use App\Models\Blood\BloodUnit;
use App\Support\Dashboard\DashboardPeriod;
use App\Support\Dashboard\Days;
use App\Support\Dashboard\Spread;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The figures of the blood bank dashboard. Now: usable bags by component against the policy minimum, bags expiring inside the warning window, requests waiting, bags issued today and bags on the ward not yet started, open incidents.
 * In the period (and the period before): bags issued, how long from the request to the first issue (median and 90th percentile), and wastage (discarded or expired as a share of bags received). No patient is named here.
 */
class BloodOverview
{
    public function __construct(private readonly BloodPolicy $policy, private readonly BloodUtilizationReport $utilization) {}

    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, DashboardPeriod $period, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();
        $today = Days::today($now);
        $units = fn () => BloodUnit::query()->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $requests = fn () => DB::table('blood_requests')->where('blood_requests.organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('blood_requests.branch_id', $branchId));
        $issues = fn () => DB::table('blood_issues')->where('organization_id', $organizationId);
        $in = function (DashboardPeriod $p) use ($requests, $issues, $organizationId) {
            $wait = $requests()->join('blood_issues as i', 'i.request_id', '=', 'blood_requests.id')->groupBy('blood_requests.id', 'blood_requests.requested_at')->selectRaw('blood_requests.requested_at as requested_at, min(i.issued_at) as first_at')
                ->havingRaw('min(i.issued_at) between ? and ?', [$p->fromUtc(), $p->toUtc()])->get()->map(fn ($r) => Spread::minutesBetween($r->requested_at, $r->first_at));
            $use = collect($this->utilization->summary($organizationId, $p->fromUtc(), $p->toUtc()));
            $received = (int) $use->sum('received');

            return ['issued' => (int) $issues()->whereBetween('issued_at', [$p->fromUtc(), $p->toUtc()])->count(), 'wait' => Spread::of($wait),
                'wastage_percent' => $received > 0 ? round(100 * ($use->sum('discarded') + $use->sum('expired')) / $received, 1) : null];
        };
        $available = $units()->where('status', BloodUnit::AVAILABLE)->where('expires_at', '>', $now)->selectRaw('component, count(*) as total')->groupBy('component')->pluck('total', 'component');
        $minimum = (array) $this->policy->get('low_stock');
        $stock = collect((array) $this->policy->get('components'))->map(fn ($c, $code) => ['component' => $c['label'], 'code' => $code, 'available' => (int) ($available[$code] ?? 0), 'minimum' => isset($minimum[$code]) ? (int) $minimum[$code] : null])->values()->all();

        return [
            'stock' => $stock, 'below_minimum' => count(array_filter($stock, fn ($s) => $s['minimum'] !== null && $s['available'] < $s['minimum'])),
            'expiring' => (int) $units()->where('status', BloodUnit::AVAILABLE)->whereBetween('expires_at', [$now, $now->addDays((int) $this->policy->get('expiry_alert_days'))])->count(),
            'expiry_days' => (int) $this->policy->get('expiry_alert_days'),
            'requests_waiting' => (int) $requests()->whereIn('status', ['requested', 'sample_received', 'ready', 'partially_issued'])->count(),
            'emergency_waiting' => (int) $requests()->whereIn('status', ['requested', 'sample_received', 'ready', 'partially_issued'])->whereIn('priority', ['emergency', 'massive'])->count(),
            'issued_today' => (int) $issues()->whereBetween('issued_at', Days::bounds($today))->count(),
            'on_ward_not_started' => (int) $issues()->where('status', 'issued')->count(),
            'open_incidents' => (int) DB::table('blood_incidents')->where('organization_id', $organizationId)->where('status', '!=', 'closed')->count(),
            'current' => $in($period), 'previous' => $in($period->previous()),
            'per_day' => collect(Days::perDay($issues(), 'issued_at', $period->from, $period->to))->map(fn ($n, $d) => ['day' => CarbonImmutable::parse($d)->format('d M'), 'issued' => (int) $n])->values()->all(),
        ];
    }
}
