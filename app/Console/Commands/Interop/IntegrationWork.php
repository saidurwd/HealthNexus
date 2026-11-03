<?php

namespace App\Console\Commands\Interop;

use App\Services\Interop\DeliveryWorker;
use Illuminate\Console\Command;

/** The sender (guideline override 7/8): takes due outbound messages, hands them to their transport, records every try, retries with back-off and dead-letters the hopeless ones. Run by the scheduler every minute or under a supervisor with --loop. */
class IntegrationWork extends Command
{
    protected $signature = 'integration:work {--limit= : most messages this run} {--loop : keep running until stopped} {--sleep=5 : seconds between rounds with --loop}';

    protected $description = 'Send queued integration messages (with retry and dead-letter)';

    public function handle(DeliveryWorker $worker): int
    {
        $stop = false;
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            foreach ([SIGTERM, SIGINT] as $sig) {
                pcntl_signal($sig, function () use (&$stop) {
                    $stop = true;
                });
            }
        }
        do {
            $r = $worker->run($this->option('limit') !== null ? (int) $this->option('limit') : null);
            if ($r['sent'] + $r['retry'] + $r['dead'] > 0 || ! $this->option('loop')) {
                $this->info("sent {$r['sent']}, will retry {$r['retry']}, dead {$r['dead']}");
            }
            if ($this->option('loop') && ! $stop) {
                sleep(max(1, (int) $this->option('sleep')));
            }
        } while ($this->option('loop') && ! $stop);

        return self::SUCCESS;
    }
}
