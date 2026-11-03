<?php

namespace App\Console\Commands\Welfare;

use App\Services\Welfare\WelfareCheck;
use Illuminate\Console\Command;

class WelfareCheckCommand extends Command
{
    protected $signature = 'welfare:check';

    protected $description = 'The daily welfare round: lapse old assessments and grants, charge funds for discounts Billing approved, and raise what waits too long or runs low.';

    public function handle(WelfareCheck $check): int
    {
        $this->info('Checked '.$check->runAll().' hospital(s).');

        return self::SUCCESS;
    }
}
