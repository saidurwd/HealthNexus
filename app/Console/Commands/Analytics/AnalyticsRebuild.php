<?php

namespace App\Console\Commands\Analytics;

use App\Services\Analytics\LoadRunner;
use Illuminate\Console\Command;

/** Disaster recovery for the warehouse: it holds nothing that the operational tables cannot rebuild. */
class AnalyticsRebuild extends Command
{
    protected $signature = 'analytics:rebuild {--force : do not ask}';

    protected $description = 'Empty the warehouse and load everything again from the operational tables';

    public function handle(LoadRunner $runner): int
    {
        if (! $this->option('force') && ! $this->confirm('Empty the warehouse and load it again? Dashboards are empty until it finishes.')) {
            return self::SUCCESS;
        }
        $runs = $runner->rebuild();
        $bad = collect($runs)->where('status', 'failed')->count();
        $this->info(count($runs).' loaders ran, '.$bad.' failed.');

        return $bad ? self::FAILURE : self::SUCCESS;
    }
}
