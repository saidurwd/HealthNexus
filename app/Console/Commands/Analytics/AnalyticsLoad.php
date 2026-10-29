<?php

namespace App\Console\Commands\Analytics;

use App\Services\Analytics\AnalyticsInstaller;
use App\Services\Analytics\DataQualityService;
use App\Services\Analytics\LoadRunner;
use Illuminate\Console\Command;

/** Fill the warehouse from the operational tables: changed rows only, each loader on its own, then the data-quality rules. */
class AnalyticsLoad extends Command
{
    protected $signature = 'analytics:load {--only=* : loader keys, e.g. fact_invoices} {--no-quality : skip the data-quality rules}';

    protected $description = 'Load the warehouse (incremental) and check its quality';

    public function handle(LoadRunner $runner, DataQualityService $quality, AnalyticsInstaller $installer): int
    {
        $installer->install();
        $runs = $runner->run($this->option('only') ?: null);
        $failed = 0;
        foreach ($runs as $key => $run) {
            $this->line(sprintf('%-22s %-7s read %-6d written %-6d %s', $key, $run->status, $run->rows_read, $run->rows_written, $run->error ?? ''));
            $failed += $run->status === 'failed' ? 1 : 0;
        }
        if (! $this->option('no-quality')) {
            $q = $quality->run();
            $this->info("Data quality: {$q['failing']} rule failure(s), {$q['opened']} new issue(s), {$q['closed']} cleared.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
