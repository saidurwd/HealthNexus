<?php

namespace App\Console\Commands\Ot;

use App\Jobs\Ot\DetectScheduleConflicts;
use Illuminate\Console\Command;

class DetectScheduleConflictsCommand extends Command
{
    protected $signature = 'ot:detect-schedule-conflicts';

    protected $description = 'Report any overlapping theatre bookings';

    public function handle(): int
    {
        DetectScheduleConflicts::dispatchSync();

        $this->info('Report any overlapping theatre bookings — done.');

        return self::SUCCESS;
    }
}
