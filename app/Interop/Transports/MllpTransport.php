<?php

namespace App\Interop\Transports;

use App\Interop\Hl7\Hl7Message;
use App\Interop\Hl7\Mllp;
use App\Models\Integration\IntegrationEndpoint;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use Throwable;

/** Sends an HL7 v2 message over MLLP and reads the ACK: AA delivered, AE the other side had a problem (try again later), AR the other side refused it for good. */
class MllpTransport implements Transport
{
    public function send(IntegrationMessage $message, IntegrationEndpoint $endpoint, IntegrationPartner $partner): TransportResult
    {
        [$host, $port] = array_pad(explode(':', $endpoint->url, 2), 2, '2575');
        $socket = @stream_socket_client("tcp://$host:$port", $errno, $error, $endpoint->timeout_seconds ?: 10);
        if (! $socket) {
            return TransportResult::retry("could not connect to $host:$port ($error)");
        }
        try {
            stream_set_timeout($socket, $endpoint->timeout_seconds ?: 10);
            fwrite($socket, Mllp::frame((string) $message->payload));
            $buffer = '';
            $frames = [];
            while ($frames === [] && ! feof($socket)) {
                $chunk = fread($socket, 8192);
                if ($chunk === '' || $chunk === false) {
                    if (stream_get_meta_data($socket)['timed_out']) {
                        return TransportResult::retry('no acknowledgement in time');
                    }
                    break;
                }
                $buffer .= $chunk;
                $frames = Mllp::extract($buffer);
            }
            if ($frames === []) {
                return TransportResult::retry('the other side closed without an acknowledgement');
            }
            $ack = Hl7Message::parse($frames[0]);
            $code = $ack->get('MSA-1');
            $text = mb_substr($ack->get('MSA-3'), 0, 200);

            return match ($code) {
                'AA', 'CA' => TransportResult::ok(null, 'accepted (AA)', $frames[0]),
                'AR', 'CR' => TransportResult::refused("rejected (AR): $text", null, $frames[0]),
                default => TransportResult::retry("application error ($code): $text", null, $frames[0]),
            };
        } catch (Throwable $e) {
            return TransportResult::retry('error talking MLLP: '.mb_substr($e->getMessage(), 0, 200));
        } finally {
            fclose($socket);
        }
    }
}
