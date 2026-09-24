<?php

namespace App\Console\Commands\Nursing;

use App\Jobs\Nursing\GenerateMedicationAdministrationSchedule;
use Illuminate\Console\Command;

class GenerateMedicationAdministrationScheduleCommand extends Command
{
    protected $signature = 'nursing:generate-medication-schedule';

    protected $description = 'Generate the next scheduled MAR dose where a configured frequency matches';

    public function handle(): int
    {
        GenerateMedicationAdministrationSchedule::dispatchSync();

        $this->info('Generate the next scheduled MAR dose where a configured frequency matches — done.');

        return self::SUCCESS;
    }
}
