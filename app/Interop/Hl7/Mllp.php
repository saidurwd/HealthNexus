<?php

namespace App\Interop\Hl7;

/** MLLP framing: <VT> message <FS><CR>. Pure functions so the listener, the sender and the tests share one definition. */
final class Mllp
{
    public const START = "\x0b";

    public const END = "\x1c\r";

    public static function frame(string $message): string
    {
        return self::START.rtrim($message, "\r\n")."\r".self::END;
    }

    /**
     * Pulls every complete frame out of $buffer (which keeps any unfinished tail).
     *
     * @return list<string>
     */
    public static function extract(string &$buffer): array
    {
        $out = [];
        while (($start = strpos($buffer, self::START)) !== false && ($end = strpos($buffer, self::END, $start)) !== false) {
            $out[] = substr($buffer, $start + 1, $end - $start - 1);
            $buffer = substr($buffer, $end + 2);
        }

        return $out;
    }
}
