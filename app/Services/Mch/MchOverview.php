<?php

namespace App\Services\Mch;

use App\Support\Dashboard\DashboardPeriod;
use App\Support\Dashboard\Days;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The figures of the Mothers and babies page. Now: women being followed, how many are high-risk (a risk factor at booking, or an alert at the latest visit), due to deliver within 30 days, past their date, with no visit for 6 weeks;
 * children with a vaccine due or overdue. In the period (and the one before): deliveries, caesarean share, stillbirths, low-birth-weight share (under 2,500 g), antenatal visits, doses given. Totals only: no one is named.
 */
class MchOverview
{
    public function __construct(private readonly ImmunisationService $immunisation) {}

    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, DashboardPeriod $period, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();
        $today = Days::today($now)->toDateString();
        $preg = fn () => DB::table('mch_pregnancies')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $deliveries = fn () => DB::table('mch_deliveries')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $active = $preg()->where('status', 'active')->get(['id', 'edd', 'risk_factors', 'booked_at']);
        $lastVisit = DB::table('mch_anc_visits')->whereIn('pregnancy_id', $active->pluck('id'))->selectRaw('pregnancy_id, max(visited_at) as last_at')->groupBy('pregnancy_id')->pluck('last_at', 'pregnancy_id');
        $latestAlerts = DB::table('mch_anc_visits as v')->whereIn('v.pregnancy_id', $active->pluck('id'))->whereRaw('v.visit_no = (select max(visit_no) from mch_anc_visits where pregnancy_id = v.pregnancy_id)')->pluck('v.alerts', 'v.pregnancy_id');
        $stale = $now->subWeeks(6);
        $highRisk = $active->filter(fn ($p) => json_decode((string) $p->risk_factors, true) || json_decode((string) ($latestAlerts[$p->id] ?? '[]'), true))->count();
        $in = function (DashboardPeriod $p) use ($deliveries, $organizationId) {
            $d = $deliveries()->whereBetween('delivered_at', [$p->fromUtc(), $p->toUtc()]);
            $n = (int) (clone $d)->count();
            $babies = DB::table('mch_newborns as b')->join('mch_deliveries as d', 'd.id', '=', 'b.delivery_id')->where('d.organization_id', $organizationId)->whereBetween('d.delivered_at', [$p->fromUtc(), $p->toUtc()]);
            $live = (int) (clone $babies)->where('b.outcome', 'live_birth')->count();

            return [
                'deliveries' => $n, 'caesarean_percent' => $n > 0 ? round(100 * (clone $d)->where('mode', 'caesarean')->count() / $n, 1) : null,
                'stillbirths' => (int) (clone $babies)->where('b.outcome', 'stillbirth')->count(),
                'low_weight_percent' => $live > 0 ? round(100 * (clone $babies)->where('b.outcome', 'live_birth')->whereNotNull('b.weight_g')->where('b.weight_g', '<', 2500)->count() / $live, 1) : null,
                'anc_visits' => (int) DB::table('mch_anc_visits')->where('organization_id', $organizationId)->whereBetween('visited_at', [$p->fromUtc(), $p->toUtc()])->count(),
                'doses' => (int) DB::table('mch_immunisations')->where('organization_id', $organizationId)->whereBetween('given_at', [$p->fromUtc(), $p->toUtc()])->count(),
            ];
        };
        $due = $this->immunisation->dueList($organizationId, $now);

        return [
            'pregnant' => $active->count(), 'high_risk' => $highRisk,
            'due_30' => $active->filter(fn ($p) => $p->edd >= $today && $p->edd <= $now->addDays(30)->toDateString())->count(),
            'past_date' => $active->filter(fn ($p) => $p->edd < $today)->count(),
            'no_visit_6w' => $active->filter(fn ($p) => CarbonImmutable::parse((string) ($lastVisit[$p->id] ?? $p->booked_at), 'UTC')->lt($stale))->count(),
            'children_due' => $due->count(), 'children_overdue' => $due->where('overdue', '>', 0)->count(),
            'current' => $in($period), 'previous' => $in($period->previous()),
            'by_mode' => collect(config('mch.delivery_modes'))->map(fn ($m) => ['mode' => ucfirst($m), 'deliveries' => (int) $deliveries()->where('mode', $m)->whereBetween('delivered_at', [$period->fromUtc(), $period->toUtc()])->count()])->filter(fn ($r) => $r['deliveries'] > 0)->values()->all(),
            'per_day' => collect(Days::perDay($deliveries(), 'delivered_at', $period->from, $period->to))->map(fn ($n, $d) => ['day' => CarbonImmutable::parse($d)->format('d M'), 'deliveries' => (int) $n])->values()->all(),
        ];
    }
}
