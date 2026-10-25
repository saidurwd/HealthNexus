<?php

namespace App\Console\Commands\Icu;

use App\Jobs\Icu\DetectMissingIcuDocumentation;
use Illuminate\Console\Command;

class DetectMissingIcuDocumentationCommand extends Command
{
    protected $signature = 'icu:detect-missing-documentation';

    protected $description = 'Report active ICU episodes missing recent progress notes or observations';

    public function handle(): int
    {
        DetectMissingIcuDocumentation::dispatchSync();

        $this->info('ICU documentation check complete.');

        return self::SUCCESS;
    }
}
