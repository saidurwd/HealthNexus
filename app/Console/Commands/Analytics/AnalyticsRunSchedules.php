<?php

namespace App\Console\Commands\Analytics;

use App\Services\Analytics\Reports\ScheduleService;
use Illuminate\Console\Command;

class AnalyticsRunSchedules extends Command
{
    protected $signature = 'analytics:run-schedules';

    protected $description = 'Send the scheduled reports that are due';

    public function handle(ScheduleService $schedules): int
    {
        $r = $schedules->runDue();
        $this->info("{$r['sent']} report(s) sent, {$r['failed']} failed.");

        return self::SUCCESS;
    }
}
