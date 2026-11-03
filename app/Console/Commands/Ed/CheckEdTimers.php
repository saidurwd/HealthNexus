<?php

namespace App\Console\Commands\Ed;

use App\Services\Ed\EdTimerService;
use Illuminate\Console\Command;

class CheckEdTimers extends Command
{
    protected $signature = 'ed:check-timers';

    protected $description = 'Raise alerts for ED patients overdue for triage, re-triage or a doctor';

    public function handle(EdTimerService $timers): int
    {
        $this->info($timers->check().' alert(s) raised.');

        return self::SUCCESS;
    }
}
