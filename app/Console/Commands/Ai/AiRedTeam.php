<?php

namespace App\Console\Commands\Ai;

use App\Services\Ai\RedTeam;
use Illuminate\Console\Command;

class AiRedTeam extends Command
{
    protected $signature = 'ai:redteam';

    protected $description = 'Run the red-team pack against the AI guards (instructions hidden in text, actions in output, a misbehaving model). Fails if any guard no longer holds.';

    public function handle(RedTeam $pack): int
    {
        $r = $pack->run();
        foreach ($r['failed'] as $f) {
            $this->error($f);
        }
        $this->line("{$r['passed']} of {$r['total']} checks held.");

        return $r['failed'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
