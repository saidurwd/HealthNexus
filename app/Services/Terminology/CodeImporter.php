<?php

namespace App\Services\Terminology;

use App\Models\Terminology\CodeMap;
use App\Models\Terminology\CodeSystem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Loads a code system from an official distribution file converted to CSV. Streams the file and upserts in
 * chunks, so a LOINC- or SNOMED-sized file does not need to fit in memory. Re-importing updates in place.
 */
class CodeImporter
{
    private const CHUNK = 1000;

    /**
     * @param  array{code?: string, display_en?: string, display_bn?: string, parent_code?: string, status?: string}  $columns  CSV header names
     * @return array{rows: int, skipped: int}
     */
    public function importConcepts(CodeSystem $system, string $path, array $columns = [], string $delimiter = ',', ?string $version = null): array
    {
        $col = $columns + ['code' => 'code', 'display_en' => 'display_en', 'display_bn' => 'display_bn', 'parent_code' => 'parent_code', 'status' => 'status'];
        $rows = $skipped = 0;
        $batch = [];

        $this->eachRow($path, $delimiter, [$col['code'], $col['display_en']], function (array $row) use ($system, $col, &$batch, &$rows, &$skipped) {
            $code = trim($row[$col['code']] ?? '');
            $display = trim($row[$col['display_en']] ?? '');

            if ($code === '' || $display === '') {
                $skipped++;

                return;
            }

            $batch[] = [
                'code_system_id' => $system->id,
                'code' => $code,
                'display_en' => mb_substr($display, 0, 500),
                'display_bn' => ($row[$col['display_bn']] ?? '') !== '' ? mb_substr($row[$col['display_bn']], 0, 500) : null,
                'parent_code' => ($row[$col['parent_code']] ?? '') !== '' ? $row[$col['parent_code']] : null,
                'status' => ($row[$col['status']] ?? '') !== '' ? $row[$col['status']] : 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $rows++;

            if (count($batch) >= self::CHUNK) {
                $this->flush($batch);
                $batch = [];
            }
        });

        $this->flush($batch);
        $system->update(['loaded_at' => now(), 'version' => $version ?? $system->version]);

        return ['rows' => $rows, 'skipped' => $skipped];
    }

    /**
     * Maps between two already-loaded systems (e.g. ICD-10 ↔ ICD-11). Rows whose codes are not in the
     * registry are skipped rather than creating dangling maps.
     *
     * @return array{rows: int, skipped: int}
     */
    public function importMaps(CodeSystem $from, CodeSystem $to, string $path, string $delimiter = ','): array
    {
        $rows = $skipped = 0;

        $this->eachRow($path, $delimiter, ['from_code', 'to_code'], function (array $row) use ($from, $to, &$rows, &$skipped) {
            $exists = fn (CodeSystem $s, string $code) => DB::table('code_concepts')->where('code_system_id', $s->id)->where('code', $code)->exists();

            if (! $exists($from, $row['from_code']) || ! $exists($to, $row['to_code'])) {
                $skipped++;

                return;
            }

            CodeMap::updateOrCreate(
                ['from_system_id' => $from->id, 'from_code' => $row['from_code'], 'to_system_id' => $to->id, 'to_code' => $row['to_code']],
                ['relationship' => ($row['relationship'] ?? '') ?: 'equivalent'],
            );
            $rows++;
        });

        return ['rows' => $rows, 'skipped' => $skipped];
    }

    private function flush(array $batch): void
    {
        if ($batch !== []) {
            DB::table('code_concepts')->upsert($batch, ['code_system_id', 'code'], ['display_en', 'display_bn', 'parent_code', 'status', 'updated_at']);
        }
    }

    private function eachRow(string $path, string $delimiter, array $required, callable $each): void
    {
        if (! is_readable($path)) {
            throw new InvalidArgumentException("Cannot read [$path].");
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, 0, $delimiter);

        if ($header === false) {
            throw new InvalidArgumentException('The file is empty.');
        }

        $header = array_map(fn ($h) => trim(ltrim((string) $h, "\xEF\xBB\xBF")), $header); // strip UTF-8 BOM

        foreach ($required as $name) {
            if (! in_array($name, $header, true)) {
                throw new InvalidArgumentException("Column [$name] not found. Header is: ".implode(', ', $header));
            }
        }

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($line === [null]) {
                continue;
            }
            $each(array_combine($header, array_pad($line, count($header), '')) ?: []);
        }

        fclose($handle);
    }
}
