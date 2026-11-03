<?php

namespace App\Services\Ed;

use App\Support\Dashboard\DashboardPeriod;
use App\Support\Dashboard\Days;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * What the Emergency performance dashboard shows for a period: the department's own figures (EdKpiService) for the period and for the period before, the arrivals per day, and the arrivals by hour of the day in Dhaka time.
 * Plain arrays, so it can be cached.
 */
class EdPerformance
{
    public function __construct(private readonly EdKpiService $kpis) {}

    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, DashboardPeriod $period): array
    {
        $visits = fn () => DB::table('ed_visits')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $hours = $visits()->whereBetween('arrival_at', [$period->fromUtc(), $period->toUtc()])->selectRaw(Days::hourOf('arrival_at').' as h, count(*) as n')->groupBy('h')->pluck('n', 'h');
        $previous = $period->previous();

        return [
            'kpi' => $this->kpis->summary($organizationId, $branchId, $period->fromUtc(), $period->toUtc()),
            'previous' => $this->kpis->summary($organizationId, $branchId, $previous->fromUtc(), $previous->toUtc()),
            'per_day' => collect(Days::perDay($visits(), 'arrival_at', $period->from, $period->to))->map(fn ($n, $d) => ['day' => CarbonImmutable::parse($d)->format('d M'), 'arrivals' => (int) $n])->values()->all(),
            'by_hour' => collect(range(0, 23))->map(fn ($h) => ['hour' => sprintf('%02d', $h), 'arrivals' => (int) ($hours[$h] ?? 0)])->all(),
        ];
    }
}
