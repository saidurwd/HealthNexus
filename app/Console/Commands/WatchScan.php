<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\ModuleManager;
use App\Services\Watch\SurveillanceService;
use Illuminate\Console\Command;

class WatchScan extends Command
{
    protected $signature = 'watch:scan';

    protected $description = 'Look at the diagnoses for the conditions being watched and raise a signal where cases are rising.';

    public function handle(SurveillanceService $surveillance, ModuleManager $modules): int
    {
        $raised = 0;
        foreach (Organization::query()->orderBy('id')->get() as $org) {
            $modules->isEnabled('watch', (int) $org->id) && $raised += $surveillance->scan((int) $org->id)['raised'];
        }
        $this->info("{$raised} signal(s) raised.");

        return self::SUCCESS;
    }
}
