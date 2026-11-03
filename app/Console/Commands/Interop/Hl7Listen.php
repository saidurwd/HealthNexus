<?php

namespace App\Console\Commands\Interop;

use App\Interop\Hl7\Hl7Listener;
use Illuminate\Console\Command;

/**
 * The HL7 v2 listener (guideline override 7): a separate long-running process under its own supervisor, never part of a web request. Accepts MLLP connections one at a time and answers every
 * message with an ACK. Run: `php artisan hl7:listen` (see docs/phase18 for the supervisor example).
 */
class Hl7Listen extends Command
{
    protected $signature = 'hl7:listen {--host= : address to listen on} {--port= : TCP port} {--once : stop after the first connection (testing)}';

    protected $description = 'Listen for HL7 v2 messages over MLLP and answer each with an ACK';

    public function handle(Hl7Listener $listener): int
    {
        $host = $this->option('host') ?: config('interop.hl7.listen_host');
        $port = (int) ($this->option('port') ?: config('interop.hl7.listen_port'));
        $server = @stream_socket_server("tcp://$host:$port", $errno, $error);
        if (! $server) {
            $this->error("Could not listen on $host:$port ($error)");

            return self::FAILURE;
        }
        $this->info("HL7 listener ready on $host:$port");
        $stop = false;
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            foreach ([SIGTERM, SIGINT] as $sig) {
                pcntl_signal($sig, function () use (&$stop) {
                    $stop = true;
                });
            }
        }
        while (! $stop) {
            $conn = @stream_socket_accept($server, 5);
            if (! $conn) {
                continue;
            }
            try {
                $listener->serve($conn);
            } catch (\Throwable $e) {
                report($e);
            } finally {
                fclose($conn);
            }
            if ($this->option('once')) {
                break;
            }
        }
        fclose($server);
        $this->info('HL7 listener stopped');

        return self::SUCCESS;
    }
}
