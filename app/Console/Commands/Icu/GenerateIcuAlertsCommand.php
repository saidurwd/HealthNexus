<?php

namespace App\Console\Commands\Icu;

use App\Jobs\Icu\GenerateIcuAlerts;
use Illuminate\Console\Command;

class GenerateIcuAlertsCommand extends Command
{
    protected $signature = 'icu:generate-alerts';

    protected $description = 'Scan ICU observations against configured alert thresholds';

    public function handle(): int
    {
        GenerateIcuAlerts::dispatchSync();

        $this->info('ICU alert scan complete.');

        return self::SUCCESS;
    }
}
