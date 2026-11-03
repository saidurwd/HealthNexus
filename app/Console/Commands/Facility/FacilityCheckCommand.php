<?php

namespace App\Console\Commands\Facility;

use App\Services\Facility\FacilityCheck;
use Illuminate\Console\Command;

class FacilityCheckCommand extends Command
{
    protected $signature = 'facility:check';

    protected $description = 'The daily facility round: work orders for plans that fall due, warnings, packs past their date, ended diets and passes, mortuary cases.';

    public function handle(FacilityCheck $check): int
    {
        $this->info('Checked '.$check->runAll().' hospital(s).');

        return self::SUCCESS;
    }
}
