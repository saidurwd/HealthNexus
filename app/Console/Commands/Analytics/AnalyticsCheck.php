<?php

namespace App\Console\Commands\Analytics;

use App\Models\Organization;
use App\Services\Analytics\DataQualityService;
use App\Services\Analytics\LoadRunner;
use App\Services\ModuleManager;
use Illuminate\Console\Command;

/** Hourly watch: loads that failed or went stale are reported as an alert, and the data-quality rules are run again. */
class AnalyticsCheck extends Command
{
    protected $signature = 'analytics:check';

    protected $description = 'Report stale or failed warehouse loads and run the data-quality rules';

    public function handle(LoadRunner $runner, DataQualityService $quality, ModuleManager $modules): int
    {
        $bad = $runner->problems();
        foreach (Organization::query()->where('is_active', true)->get() as $org) {
            $modules->isEnabled('analytics', $org->id) && $runner->alertStale($org->id);
        }
        $q = $quality->run();
        $this->info($bad->count().' load problem(s); data quality: '.$q['failing'].' failing rule(s), '.$quality->openCount().' open issue(s).');

        return self::SUCCESS;
    }
}
