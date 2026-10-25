<?php

namespace App\Console\Commands\Ot;

use App\Jobs\Ot\DetectMissingPreoperativeRequirements;
use Illuminate\Console\Command;

class DetectMissingPreoperativeRequirementsCommand extends Command
{
    protected $signature = 'ot:detect-missing-preoperative-requirements';

    protected $description = 'Notify staff of surgeries missing consent, assessment, or checklist completion within 24 hours';

    public function handle(): int
    {
        DetectMissingPreoperativeRequirements::dispatchSync();

        $this->info('Notify staff of surgeries missing consent, assessment, or checklist completion within 24 hours — done.');

        return self::SUCCESS;
    }
}
