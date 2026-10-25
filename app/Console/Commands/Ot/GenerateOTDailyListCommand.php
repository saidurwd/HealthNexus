<?php

namespace App\Console\Commands\Ot;

use App\Jobs\Ot\GenerateOTDailyList;
use Illuminate\Console\Command;

class GenerateOTDailyListCommand extends Command
{
    protected $signature = 'ot:generate-daily-list';

    protected $description = "Notify OT coordination roles that tomorrow's operating list is ready for review";

    public function handle(): int
    {
        GenerateOTDailyList::dispatchSync();

        $this->info('Daily operating list notification dispatched.');

        return self::SUCCESS;
    }
}
