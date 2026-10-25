<?php

namespace App\Console\Commands\Icu;

use App\Jobs\Icu\GenerateIcuDailyCensus;
use Illuminate\Console\Command;

class GenerateIcuDailyCensusCommand extends Command
{
    protected $signature = 'icu:generate-daily-census';

    protected $description = 'Log the daily ICU census per company';

    public function handle(): int
    {
        GenerateIcuDailyCensus::dispatchSync();

        $this->info('ICU daily census generated.');

        return self::SUCCESS;
    }
}
