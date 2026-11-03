<?php

namespace App\Console\Commands\Analytics;

use App\Models\Analytics\AnalyticsKpi;
use App\Models\Organization;
use App\Services\Analytics\Kpi\KpiService;
use App\Services\Analytics\Semantic\ScopeContext;
use App\Services\Analytics\Warehouse;
use Illuminate\Console\Command;

/** How fast the dashboards are: every standard KPI for the first hospital, and the size of the warehouse tables. For capacity planning and for the gate. */
class AnalyticsBenchmark extends Command
{
    protected $signature = 'analytics:benchmark {--organization= : organization id (default: the first)}';

    protected $description = 'Time every standard KPI and show the warehouse table sizes';

    public function handle(KpiService $kpis, Warehouse $warehouse): int
    {
        $org = (int) ($this->option('organization') ?: Organization::query()->value('id'));
        $scope = ScopeContext::system($org);
        $total = 0.0;
        foreach (AnalyticsKpi::query()->whereNull('organization_id')->orderBy('code')->get() as $kpi) {
            $t = microtime(true);
            $r = $kpis->evaluate($kpi, $scope);
            $ms = (microtime(true) - $t) * 1000;
            $total += $ms;
            $this->line(sprintf('%-28s %8.1f ms   %s', $kpi->code, $ms, $r['value'] === null ? '—' : $r['value']));
        }
        $this->info(sprintf('All KPIs: %.0f ms.', $total));
        foreach (['dim_patient', 'fact_encounters', 'fact_invoices', 'fact_revenue_lines', 'fact_daily_metrics'] as $t) {
            $this->line(sprintf('%-22s %d rows', $t, $warehouse->table($t)->count()));
        }

        return self::SUCCESS;
    }
}
