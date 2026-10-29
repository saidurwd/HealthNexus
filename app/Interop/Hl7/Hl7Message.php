<?php

namespace App\Interop\Hl7;

use InvalidArgumentException;

/**
 * An HL7 v2 message read into segments, fields, repetitions, components and sub-components, with the escape sequences undone. Addresses are written like the standard writes them:
 * `PID-5.1` (field 5, component 1), `PID-3[1].1` (second repetition), `OBX[2]-5` (second OBX segment). Anything missing reads as an empty string, never an error, because senders differ.
 */
final class Hl7Message
{
    /** @param list<list<string>> $segments raw fields of each segment; field 0 is the segment name (for MSH, field 1 is the separator itself) */
    private function __construct(private array $segments, public readonly string $fieldSep, public readonly string $compSep, public readonly string $repSep, public readonly string $escChar, public readonly string $subSep) {}

    public static function parse(string $raw): self
    {
        $raw = ltrim(str_replace("\x0b", '', trim($raw, "\x0b\x1c\r\n \t")));
        if (! str_starts_with($raw, 'MSH') || strlen($raw) < 8) {
            throw new InvalidArgumentException('Not an HL7 v2 message: it must start with MSH.');
        }
        $fieldSep = $raw[3];
        $enc = substr($raw, 4, 4);
        [$comp, $rep, $esc, $sub] = [$enc[0] ?? '^', $enc[1] ?? '~', $enc[2] ?? '\\', $enc[3] ?? '&'];
        $segments = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $fields = explode($fieldSep, $line);
            if ($fields[0] === 'MSH') {
                array_splice($fields, 1, 0, [$fieldSep]); // MSH-1 is the separator, so MSH-3 lines up with the standard's numbering
            }
            $segments[] = $fields;
        }

        return new self($segments, $fieldSep, $comp, $rep, $esc, $sub);
    }

    /** @return list<string> */
    public function segmentNames(): array
    {
        return array_map(fn ($s) => $s[0], $this->segments);
    }

    public function count(string $segment): int
    {
        return count(array_filter($this->segments, fn ($s) => $s[0] === $segment));
    }

    /** Reads a value by address (see the class note). */
    public function get(string $address, string $default = ''): string
    {
        if (! preg_match('/^([A-Z0-9]{3})(?:\[(\d+)\])?-(\d+)(?:\[(\d+)\])?(?:\.(\d+))?(?:\.(\d+))?$/', $address, $m)) {
            throw new InvalidArgumentException("Bad HL7 address: $address");
        }
        $segs = array_values(array_filter($this->segments, fn ($s) => $s[0] === $m[1]));
        $seg = $segs[$m[2] !== '' ? (int) $m[2] - 1 : 0] ?? null;

        if ($seg === null) {
            return $default;
        }
        $value = $this->fieldValue($seg[(int) $m[3]] ?? '', isset($m[4]) && $m[4] !== '' ? (int) $m[4] - 1 : 0, ($m[5] ?? '') !== '' ? (int) $m[5] : null, ($m[6] ?? '') !== '' ? (int) $m[6] : null);

        return $value !== '' ? $value : $default;
    }

    /** Value of a field in segment number $index of $segment (0-based), by field number and component. */
    public function in(string $segment, int $index, int $field, ?int $component = null, int $repetition = 0): string
    {
        $segs = array_values(array_filter($this->segments, fn ($s) => $s[0] === $segment));

        return isset($segs[$index]) ? $this->fieldValue($segs[$index][$field] ?? '', $repetition, $component, null) : '';
    }

    /** How many repetitions a field has (for example several identifiers in PID-3). */
    public function repetitions(string $segment, int $field, int $index = 0): int
    {
        $segs = array_values(array_filter($this->segments, fn ($s) => $s[0] === $segment));
        $raw = $segs[$index][$field] ?? '';

        return $raw === '' ? 0 : count(explode($this->repSep, $raw));
    }

    private function fieldValue(string $raw, int $rep, ?int $comp, ?int $sub): string
    {
        $reps = explode($this->repSep, $raw);
        $value = $reps[$rep] ?? '';
        if ($comp !== null) {
            $value = explode($this->compSep, $value)[$comp - 1] ?? '';
            if ($sub !== null) {
                $value = explode($this->subSep, $value)[$sub - 1] ?? '';
            }
        }

        return $this->unescape($value);
    }

    public function unescape(string $v): string
    {
        if (! str_contains($v, $this->escChar)) {
            return $v;
        }
        $e = preg_quote($this->escChar, '/');

        return (string) preg_replace_callback("/$e(F|S|T|R|E|\.br|X[0-9A-Fa-f]+)$e/", fn ($m) => match (true) {
            $m[1] === 'F' => $this->fieldSep, $m[1] === 'S' => $this->compSep, $m[1] === 'T' => $this->subSep, $m[1] === 'R' => $this->repSep, $m[1] === 'E' => $this->escChar,
            $m[1] === '.br' => "\n", default => (string) hex2bin(substr($m[1], 1)),
        }, $v);
    }

    public function messageType(): string
    {
        return $this->get('MSH-9.1').'^'.$this->get('MSH-9.2');
    }

    public function controlId(): string
    {
        return $this->get('MSH-10');
    }

    public function toString(): string
    {
        return implode("\r", array_map(function (array $f) {
            if ($f[0] === 'MSH') {
                unset($f[1]);
                $f = array_values($f);
            }

            return implode($this->fieldSep, $f);
        }, $this->segments))."\r";
    }
}
