<?php

namespace App\Console\Commands\Ai;

use App\Services\Ai\AiCheck;
use Illuminate\Console\Command;

class AiCheckCommand extends Command
{
    protected $signature = 'ai:check';

    protected $description = 'The daily AI round: lapsed drafts expire, drift is looked for.';

    public function handle(AiCheck $check): int
    {
        $this->info('Checked '.$check->runAll().' hospital(s).');

        return self::SUCCESS;
    }
}
