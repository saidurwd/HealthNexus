<?php

namespace App\Console\Commands\Icu;

use App\Jobs\Icu\EscalateUnacknowledgedCriticalAlerts;
use Illuminate\Console\Command;

class EscalateUnacknowledgedCriticalAlertsCommand extends Command
{
    protected $signature = 'icu:escalate-unacknowledged-alerts';

    protected $description = 'Escalate ICU critical alerts that have gone unacknowledged';

    public function handle(): int
    {
        EscalateUnacknowledgedCriticalAlerts::dispatchSync();

        $this->info('ICU alert escalation check complete.');

        return self::SUCCESS;
    }
}
