<?php

namespace App\Console\Commands\Analytics;

use App\Models\Organization;
use App\Services\Analytics\Insight\ForecastService;
use App\Services\ModuleManager;
use Illuminate\Console\Command;

class AnalyticsForecast extends Command
{
    protected $signature = 'analytics:forecast';

    protected $description = 'Make the daily planning forecasts (advisory)';

    public function handle(ForecastService $forecasts, ModuleManager $modules): int
    {
        $n = 0;
        foreach (Organization::query()->where('is_active', true)->get() as $org) {
            if ($modules->isEnabled('analytics', $org->id)) {
                foreach (array_keys(ForecastService::METRICS) as $metric) {
                    $forecasts->run($org->id, $metric);
                    $n++;
                }
            }
        }
        $this->info("$n forecast(s) made.");

        return self::SUCCESS;
    }
}
