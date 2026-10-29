<?php

namespace App\Interop\Hl7;

/** Reads MLLP frames from one connection and answers each with its ACK. Kept apart from the socket accept loop so it can be tested with a plain stream pair. */
class Hl7Listener
{
    public function __construct(private readonly Hl7Inbound $inbound) {}

    /**
     * @param  resource  $stream
     * @return int how many messages were answered
     */
    public function serve($stream, int $maxFrames = PHP_INT_MAX, int $idleSeconds = 30): int
    {
        stream_set_timeout($stream, $idleSeconds);
        $buffer = '';
        $answered = 0;
        while ($answered < $maxFrames && ! feof($stream)) {
            $chunk = fread($stream, 65536);
            if ($chunk === '' || $chunk === false) {
                if (stream_get_meta_data($stream)['timed_out']) {
                    break;
                }
                usleep(20000);

                continue;
            }
            $buffer .= $chunk;
            if (strlen($buffer) > 5_000_000) { // a message that big is not HL7 we want
                break;
            }
            foreach (Mllp::extract($buffer) as $frame) {
                fwrite($stream, Mllp::frame($this->inbound->handle($frame)));
                $answered++;
            }
        }

        return $answered;
    }
}
