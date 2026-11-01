<?php

namespace App\Console\Commands\Quality;

use App\Services\Quality\QualityCheck;
use Illuminate\Console\Command;

class QualityCheckCommand extends Command
{
    protected $signature = 'quality:check {--days= : Look back this many days for events in other modules (default: everything)}';

    protected $description = 'The daily quality round: bring in events other modules recorded, open death reviews, work out indicators, and warn about what is late.';

    public function handle(QualityCheck $check): int
    {
        $n = $check->runAll($this->option('days') ? (int) $this->option('days') : null);
        $this->info("Checked $n hospital(s).");

        return self::SUCCESS;
    }
}
