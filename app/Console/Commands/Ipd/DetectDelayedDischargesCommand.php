<?php

namespace App\Console\Commands\Ipd;

use App\Jobs\Ipd\DetectDelayedDischarges;
use Illuminate\Console\Command;

class DetectDelayedDischargesCommand extends Command
{
    protected $signature = 'ipd:detect-delayed-discharges';

    protected $description = 'Notify IPD coordination roles about admissions past their expected discharge date';

    public function handle(): int
    {
        DetectDelayedDischarges::dispatchSync();

        $this->info('Delayed discharge detection dispatched.');

        return self::SUCCESS;
    }
}
