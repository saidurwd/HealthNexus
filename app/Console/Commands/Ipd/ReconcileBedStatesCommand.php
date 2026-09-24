<?php

namespace App\Console\Commands\Ipd;

use App\Jobs\Ipd\ReconcileBedStates;
use Illuminate\Console\Command;

class ReconcileBedStatesCommand extends Command
{
    protected $signature = 'ipd:reconcile-bed-states';

    protected $description = 'Report (never silently fix) discrepancies between bed status and active allocations';

    public function handle(): int
    {
        ReconcileBedStates::dispatchSync();

        $this->info('Bed state reconciliation dispatched.');

        return self::SUCCESS;
    }
}
