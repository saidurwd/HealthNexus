<?php

namespace App\Console\Commands\Nursing;

use App\Jobs\Nursing\DetectOverdueMedicationAdministration;
use Illuminate\Console\Command;

class DetectOverdueMedicationAdministrationCommand extends Command
{
    protected $signature = 'nursing:detect-overdue-medication-administration';

    protected $description = 'Mark due/overdue medication administrations and notify';

    public function handle(): int
    {
        DetectOverdueMedicationAdministration::dispatchSync();

        $this->info('Mark due/overdue medication administrations and notify — done.');

        return self::SUCCESS;
    }
}
