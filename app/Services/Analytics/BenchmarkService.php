<?php

namespace App\Services\Analytics;

use App\Models\Analytics\AnalyticsKpi;
use App\Models\Analytics\AnalyticsOptin;
use App\Models\User;
use App\Services\Analytics\Kpi\KpiService;
use App\Services\Analytics\Semantic\ScopeContext;

/**
 * Comparison with other hospitals, anonymously. Only hospitals that chose to take part are compared, a hospital sees the comparison only if it takes part itself, and nothing is shown unless
 * at least three others take part (so one peer cannot be worked out). Peers are never named; their figures are summarised as median and quartiles.
 */
class BenchmarkService
{
    public function __construct(private readonly KpiService $kpis) {}

    public function optIn(int $organizationId, bool $in, User $by): void
    {
        AnalyticsOptin::query()->updateOrCreate(['organization_id' => $organizationId], ['benchmark' => $in, 'updated_by' => $by->id]);
    }

    public function isIn(int $organizationId): bool
    {
        return (bool) AnalyticsOptin::query()->where('organization_id', $organizationId)->value('benchmark');
    }

    /** @return array{available:bool,reason:?string,own:?float,peers:int,median:?float,p25:?float,p75:?float,position:?string} */
    public function compare(AnalyticsKpi $kpi, int $organizationId, string $from, string $to): array
    {
        $none = ['available' => false, 'reason' => null, 'own' => null, 'peers' => 0, 'median' => null, 'p25' => null, 'p75' => null, 'position' => null];
        if ($kpi->organization_id !== null) {
            return ['reason' => 'Only standard KPIs can be compared: a hospital\'s own KPI means something only to it.'] + $none;
        }
        if (! $this->isIn($organizationId)) {
            return ['reason' => 'Your hospital has not agreed to take part in the comparison.'] + $none;
        }
        $own = $this->kpis->evaluate($kpi, ScopeContext::system($organizationId), $from, $to)['value'];
        $values = [];
        foreach (AnalyticsOptin::query()->where('benchmark', true)->where('organization_id', '!=', $organizationId)->pluck('organization_id') as $peer) {
            $v = $this->kpis->evaluate($kpi, ScopeContext::system((int) $peer), $from, $to)['value'];
            $v !== null && $values[] = $v;
        }
        if (count($values) < (int) config('analytics.benchmark.min_peers', 3)) {
            return ['reason' => 'Not enough other hospitals take part yet to show a comparison without singling one out.', 'own' => $own, 'peers' => count($values)] + $none;
        }
        sort($values);
        $q = function (float $p) use ($values) {
            $i = ($p / 100) * (count($values) - 1);
            $lo = (int) floor($i);
            $hi = (int) ceil($i);

            return round($values[$lo] + ($values[$hi] - $values[$lo]) * ($i - $lo), 2);
        };
        $median = $q(50);
        $position = $own === null ? null : ($own < $q(25) ? 'below the middle half' : ($own > $q(75) ? 'above the middle half' : 'within the middle half'));

        return ['available' => true, 'reason' => null, 'own' => $own, 'peers' => count($values), 'median' => $median, 'p25' => $q(25), 'p75' => $q(75), 'position' => $position];
    }
}
