<?php

namespace App\Console\Commands\Interop;

use App\Interop\Hl7\Hl7Message;
use App\Models\Patient;
use App\Services\Interop\FhirService;
use App\Services\Interop\MessageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * A rough speed check for the integration path (the load test of spec 67): how many HL7 messages a second can be read, how many patients a second can be turned into FHIR, how many messages a second can be queued.
 * It works inside a transaction that is rolled back, so it leaves nothing behind. Numbers depend on the machine; compare before and after a change, not against a promise.
 */
class BenchmarkInterop extends Command
{
    protected $signature = 'interop:benchmark {--n=500 : how many of each} {--organization= : organization id (default: the first)}';

    protected $description = 'Measure HL7 parsing, FHIR mapping and message queueing speed (nothing is kept)';

    public function handle(FhirService $fhir, MessageService $messages): int
    {
        $n = max(10, (int) $this->option('n'));
        $org = (int) ($this->option('organization') ?: DB::table('organizations')->orderBy('id')->value('id'));
        if (! $org) {
            $this->error('There is no organization.');

            return self::FAILURE;
        }
        $text = "MSH|^~\\&|BENCH|FAC|HN|HOSP|20260910100000||ADT^A04^ADT_A01|%d|P|2.5.1\rEVN|A04|20260910100000\rPID|1||B%d^^^BENCH^MR||Akter^Rina^Begum||19900412|F|||House 1^^Dhaka||01712345678\r";

        $t = microtime(true);
        for ($i = 0; $i < $n; $i++) {
            $m = Hl7Message::parse(sprintf($text, $i, $i));
            $m->get('PID-5.1');
            $m->in('PID', 0, 3, 1, 0);
        }
        $parse = $n / max(0.0001, microtime(true) - $t);

        $t = microtime(true);
        $mapper = $fhir->mapper('Patient');
        $patients = Patient::query()->where('organization_id', $org)->limit($n)->get();
        foreach ($patients as $p) {
            $mapper->toFhir($p);
        }
        $map = $patients->count() / max(0.0001, microtime(true) - $t);

        DB::beginTransaction();
        try {
            $t = microtime(true);
            for ($i = 0; $i < $n; $i++) {
                $messages->enqueue($org, null, 'hl7', 'ADT^A04', sprintf($text, $i, $i), 'bench:'.$i);
            }
            $queue = $n / max(0.0001, microtime(true) - $t);
        } finally {
            DB::rollBack();
        }

        $this->table(['Step', 'per second', 'count'], [['read an HL7 message', number_format($parse), $n], ['turn a patient into FHIR', number_format($map), $patients->count()], ['queue a message (encrypted)', number_format($queue), $n]]);

        return self::SUCCESS;
    }
}
