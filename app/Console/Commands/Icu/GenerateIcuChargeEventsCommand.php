<?php

namespace App\Console\Commands\Icu;

use App\Jobs\Icu\GenerateIcuChargeEvents;
use Illuminate\Console\Command;

class GenerateIcuChargeEventsCommand extends Command
{
    protected $signature = 'icu:generate-charge-events';

    protected $description = 'Generate and charge today\'s ICU bed-day and ventilator-day charge events';

    public function handle(): int
    {
        GenerateIcuChargeEvents::dispatchSync();

        $this->info('ICU daily charge events generated.');

        return self::SUCCESS;
    }
}
