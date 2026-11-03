<?php

namespace App\Console\Commands;

use App\Services\Reports\ReportExportService;
use Illuminate\Console\Command;

class PruneReportExports extends Command
{
    protected $signature = 'exports:prune';

    protected $description = 'Delete generated export files that have passed their expiry';

    public function handle(ReportExportService $exports): int
    {
        $this->info('Expired '.$exports->pruneExpired().' export(s).');

        return self::SUCCESS;
    }
}
