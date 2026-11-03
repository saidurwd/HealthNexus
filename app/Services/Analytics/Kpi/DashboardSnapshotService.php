<?php

namespace App\Services\Analytics\Kpi;

use App\Models\Analytics\AnalyticsDashboard;
use App\Models\Analytics\AnalyticsDashboardSnapshot;
use App\Models\Analytics\AnalyticsKpi;
use App\Models\User;
use App\Services\Analytics\Semantic\ScopeContext;
use Carbon\CarbonImmutable;

/**
 * Keeps a dashboard as it looked for a period. A person takes one by hand (with their own scope: what they may see is what is kept); the month-end pack is taken for every system dashboard with the whole hospital in scope and
 * small numbers hidden, so it is safe to hand round. Taking the same monthly snapshot twice does nothing.
 */
class DashboardSnapshotService
{
    public function __construct(private readonly DashboardService $dashboards) {}

    public function take(AnalyticsDashboard $dashboard, ScopeContext $scope, string $from, string $to, string $kind = 'manual', ?User $by = null): AnalyticsDashboardSnapshot
    {
        $widgets = json_decode((string) json_encode($this->dashboards->render($dashboard, $scope, $from, $to)), true);

        return AnalyticsDashboardSnapshot::query()->create([
            'organization_id' => $scope->organizationId, 'dashboard_code' => $dashboard->code, 'dashboard_name' => $dashboard->name, 'period_from' => $from, 'period_to' => $to, 'kind' => $kind,
            'taken_by' => $by?->id, 'widgets' => $widgets, 'checksum' => hash('sha256', (string) json_encode($widgets)),
        ]);
    }

    /** The month-end pack: every system dashboard for the calendar month. @return int snapshots taken */
    public function monthlyPack(int $organizationId, CarbonImmutable $month): int
    {
        $from = $month->startOfMonth()->toDateString();
        $to = $month->endOfMonth()->toDateString();
        $scope = new ScopeContext($organizationId, null, false);
        $taken = 0;
        foreach (AnalyticsDashboard::query()->where('is_system', true)->where(fn ($q) => $q->whereNull('organization_id')->orWhere('organization_id', $organizationId))->orderBy('id')->get() as $d) {
            $exists = AnalyticsDashboardSnapshot::query()->where(['organization_id' => $organizationId, 'dashboard_code' => $d->code, 'kind' => 'monthly', 'period_from' => $from, 'period_to' => $to])->exists();
            if (! $exists) {
                $this->take($d, $scope, $from, $to, 'monthly');
                $taken++;
            }
        }

        return $taken;
    }

    /**
     * The kept widgets in the shape the dashboard partial draws: a KPI is rebuilt from what was kept, not looked up again.
     *
     * @return list<array<string,mixed>>
     */
    public function widgets(AnalyticsDashboardSnapshot $snapshot): array
    {
        return array_map(function (array $w) {
            if (($w['type'] ?? null) === 'kpi' && isset($w['kpi']) && is_array($w['kpi'])) {
                $w['kpi'] = (new AnalyticsKpi)->forceFill($w['kpi']);
            }

            return $w;
        }, $snapshot->widgets);
    }
}
