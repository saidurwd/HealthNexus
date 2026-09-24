<?php

namespace App\Console\Commands\Ipd;

use App\Jobs\Ipd\GenerateBedOccupancyCharges;
use Illuminate\Console\Command;

class GenerateBedOccupancyChargesCommand extends Command
{
    protected $signature = 'ipd:generate-bed-occupancy-charges';

    protected $description = 'Charge every active bed occupancy for today, idempotently';

    public function handle(): int
    {
        GenerateBedOccupancyCharges::dispatchSync();

        $this->info('Bed occupancy charge generation dispatched.');

        return self::SUCCESS;
    }
}
