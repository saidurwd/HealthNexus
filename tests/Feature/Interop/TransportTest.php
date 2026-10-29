<?php

namespace Tests\Feature\Interop;

use App\Contracts\Integration\NationalExchangeGateway;
use App\Interop\Transports\MllpTransport;
use App\Models\Integration\IntegrationAccessLog;
use App\Models\Integration\IntegrationEndpoint;
use App\Models\Integration\IntegrationMessage;
use App\Services\Interop\DeliveryWorker;
use App\Services\Interop\IdMapper;
use App\Services\Interop\InteropException;
use App\Services\Interop\MessageService;
use App\Services\Interop\NationalExchangeService;

class TransportTest extends InteropTestCase
{
    /** Starts a one-connection MLLP server in another process that answers with the given ACK code; returns [process, port]. */
    private function ackServer(string $code, bool $answer = true): array
    {
        $script = <<<'PHP'
$s = stream_socket_server('tcp://127.0.0.1:0', $e, $m);
fwrite(STDOUT, explode(':', stream_socket_get_name($s, false))[1]."\n"); fflush(STDOUT);
$c = stream_socket_accept($s, 10);
$buf = ''; while (strpos($buf, "\x1c\r") === false && !feof($c)) { $buf .= fread($c, 8192); }
if (getenv('ACK_ANSWER') === '1') {
  fwrite($c, "\x0bMSH|^~\\&|X|Y|A|B|20260910||ACK^A04|1|P|2.5.1\rMSA|".getenv('ACK_CODE')."|CTRL|from the test\r\x1c\r");
}
usleep(300000);
PHP;
        $proc = proc_open([PHP_BINARY, '-r', $script], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['ACK_CODE' => $code, 'ACK_ANSWER' => $answer ? '1' : '0', 'PATH' => getenv('PATH')]);
        $port = (int) trim((string) fgets($pipes[1]));

        return [$proc, $port];
    }

    private function mllpMessage(int $port): array
    {
        $partner = $this->partner(['kind' => 'hl7_peer']);
        $endpoint = IntegrationEndpoint::create(['partner_id' => $partner->id, 'name' => 'mllp', 'transport' => 'mllp', 'url' => "127.0.0.1:$port", 'format' => 'hl7', 'timeout_seconds' => 3, 'active' => true]);
        [$m] = app(MessageService::class)->enqueue($this->organization->id, $partner, 'hl7', 'ADT^A04', "MSH|^~\\&|HN|H|X|Y|20260910||ADT^A04|CTRL|P|2.5.1\rPID|1||1\r", 'k'.$port);

        return [$m, $endpoint, $partner];
    }

    public function test_aa_means_delivered_ae_means_try_again_ar_means_never(): void
    {
        foreach (['AA' => [true, false], 'AE' => [false, true], 'AR' => [false, false]] as $code => [$ok, $retry]) {
            [$proc, $port] = $this->ackServer($code);
            [$m, $e, $p] = $this->mllpMessage($port);
            $r = (new MllpTransport)->send($m, $e, $p);
            proc_close($proc);

            $this->assertSame($ok, $r->ok, $code);
            $this->assertSame($retry, $r->retryable, $code);
            $this->assertStringContainsString($code, $r->detail === 'accepted (AA)' ? 'AA' : $r->detail);
        }
    }

    public function test_a_server_that_never_answers_or_is_not_there_is_a_retry_not_a_loss(): void
    {
        [$proc, $port] = $this->ackServer('AA', false);
        [$m, $e, $p] = $this->mllpMessage($port);
        $silent = (new MllpTransport)->send($m, $e, $p);
        proc_close($proc);
        $this->assertFalse($silent->ok);
        $this->assertTrue($silent->retryable);

        $e->update(['url' => '127.0.0.1:1']);
        $closed = (new MllpTransport)->send($m, $e->fresh(), $p);
        $this->assertTrue($closed->retryable);
        $this->assertStringContainsString('could not connect', $closed->detail);
    }

    public function test_the_worker_sends_hl7_by_mllp_end_to_end(): void
    {
        [$proc, $port] = $this->ackServer('AA');
        [$m] = $this->mllpMessage($port);
        $r = app(DeliveryWorker::class)->run();
        proc_close($proc);

        $this->assertSame(['sent' => 1, 'retry' => 0, 'dead' => 0], $r);
        $this->assertSame('processed', $m->fresh()->status);
    }

    public function test_sharing_with_the_national_exchange_needs_consent_or_a_reasoned_override_and_uses_the_test_client(): void
    {
        config(['interop.national.driver' => 'sandbox']);
        $exchange = $this->partner(['kind' => 'national_exchange', 'code' => 'NHIE', 'consent_type' => 'national_sharing']);
        IntegrationEndpoint::create(['partner_id' => $exchange->id, 'name' => 'national', 'transport' => 'national', 'url' => 'national', 'format' => 'fhir', 'active' => true]);
        $patient = $this->patient(['enterprise_patient_no' => 'EPT-000001']);
        $this->labReport($patient);
        $svc = app(NationalExchangeService::class);
        $by = $this->actor(['interoperability.national.publish']);

        try {
            $svc->share($exchange, $patient, $by);
            $this->fail('shared without consent');
        } catch (InteropException $e) {
            $this->assertSame(403, $e->status);
        }
        $this->assertSame(0, IntegrationMessage::query()->count());

        $this->consent($patient, 'national_sharing');
        $message = $svc->share($exchange, $patient, $by);
        $this->assertSame('queued', $message->status);
        $this->assertSame(['sent' => 1, 'retry' => 0, 'dead' => 0], app(DeliveryWorker::class)->run());
        $record = app(NationalExchangeGateway::class)->fetchSharedRecord($this->ids()->expose($patient));
        $this->assertTrue($record->handled);
        $this->assertSame('Patient', $record->data['record']['entry'][0]['resource']['resourceType']);
    }

    public function test_an_emergency_override_is_possible_only_with_the_right_and_a_reason_and_is_logged(): void
    {
        config(['interop.national.driver' => 'sandbox']);
        $exchange = $this->partner(['kind' => 'national_exchange', 'consent_type' => 'national_sharing']);
        $patient = $this->patient();
        $svc = app(NationalExchangeService::class);

        $plain = $this->actor(['interoperability.national.publish']);
        $this->expectException(InteropException::class);
        try {
            $svc->share($exchange, $patient, $plain, 'unconscious patient, transferred out');
        } finally {
            $this->assertSame(0, IntegrationMessage::query()->count());
            $boss = $this->actor(['interoperability.national.publish', 'interoperability.consent.override']);
            $svc->share($exchange, $patient, $boss, 'unconscious patient, transferred out');
            $this->assertSame(1, IntegrationAccessLog::query()->where('outcome', 'override')->count());
            $this->assertSame('unconscious patient, transferred out', IntegrationAccessLog::query()->where('outcome', 'override')->value('reason'));
        }
    }

    private function ids(): IdMapper
    {
        return app(IdMapper::class);
    }
}
