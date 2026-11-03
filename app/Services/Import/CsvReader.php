<?php

namespace App\Services\Import;

use App\Services\Search\Normalizer;
use InvalidArgumentException;

/** Reads a CSV upload: UTF-8 (with or without BOM), comma / semicolon / tab, Bangla digits tolerated by the importers. */
class CsvReader
{
    /** @return array{header: list<string>, rows: \Generator<int, array<string,string>>} */
    public function open(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new InvalidArgumentException('The file cannot be read.');
        }

        $first = fgets($handle);
        if ($first === false || trim($first) === '') {
            throw new InvalidArgumentException('The file is empty.');
        }

        $first = ltrim($first, "\xEF\xBB\xBF");
        $delimiter = collect([',', ';', "\t"])->sortByDesc(fn ($d) => substr_count($first, $d))->first();
        $header = array_map(fn ($h) => $this->headerKey($h), str_getcsv($first, $delimiter));

        if (count(array_filter($header)) !== count(array_unique(array_filter($header)))) {
            throw new InvalidArgumentException('The header row has a repeated column name.');
        }

        $rows = (function () use ($handle, $header, $delimiter) {
            $number = 1;
            while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($line === [null] || trim(implode('', $line)) === '') {
                    continue; // blank line
                }
                $row = [];
                foreach ($header as $i => $name) {
                    if ($name !== '') {
                        $row[$name] = trim((string) ($line[$i] ?? ''));
                    }
                }
                yield ++$number => $row;
            }
            fclose($handle);
        })();

        return ['header' => $header, 'rows' => $rows];
    }

    private function headerKey(string $header): string
    {
        return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', trim(Normalizer::latinDigits($header))), '_'));
    }
}
