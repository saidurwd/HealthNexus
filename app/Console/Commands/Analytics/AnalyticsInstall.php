<?php

namespace App\Console\Commands\Analytics;

use App\Services\Analytics\AnalyticsInstaller;
use Illuminate\Console\Command;

class AnalyticsInstall extends Command
{
    protected $signature = 'analytics:install';

    protected $description = 'Put the standard KPIs, the five role dashboards and the data-quality rules in place (safe to run again)';

    public function handle(AnalyticsInstaller $installer): int
    {
        $r = $installer->install();
        $this->info("{$r['kpis']} KPI(s), {$r['dashboards']} dashboard(s) and {$r['rules']} data-quality rule(s) added.");

        return self::SUCCESS;
    }
}
