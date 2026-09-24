<?php

namespace App\Console\Commands\Ipd;

use App\Jobs\Ipd\ExpireBedReservations;
use Illuminate\Console\Command;

class ExpireBedReservationsCommand extends Command
{
    protected $signature = 'ipd:expire-bed-reservations';

    protected $description = 'Expire bed reservations past their expiry and return the bed to Available';

    public function handle(): int
    {
        ExpireBedReservations::dispatchSync();

        $this->info('Bed reservation expiry check dispatched.');

        return self::SUCCESS;
    }
}
