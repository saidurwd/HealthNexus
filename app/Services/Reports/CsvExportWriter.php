<?php

namespace App\Services\Reports;

use App\Contracts\Reports\ReportDefinition;
use Illuminate\Support\Facades\Storage;

/** Streams rows to a file in chunks. Masks sensitive columns, neutralises spreadsheet formulas, stamps who/when. */
class CsvExportWriter
{
    private const MASK = '***';

    /** @return array{rows: int, checksum: string} */
    public function write(ReportDefinition $definition, ReportContext $context, string $disk, string $path): array
    {
        $absolute = tempnam(sys_get_temp_dir(), 'export');
        $handle = fopen($absolute, 'w');
        fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Bangla correctly

        $columns = $definition->columns();
        $canSeeSensitive = $definition->sensitivePermission() !== null && $context->user->can($definition->sensitivePermission());

        fputcsv($handle, ['# '.$definition->title().' — exported by '.$context->user->name.' (user '.$context->user->id.') on '.now()->toDateTimeString().' UTC']);
        fputcsv($handle, ['# CONFIDENTIAL — contains patient information. Do not share or forward.']);
        fputcsv($handle, array_column($columns, 'label'));

        $rows = 0;
        foreach ($definition->rows($context) as $row) {
            fputcsv($handle, array_map(function (array $column) use ($row, $canSeeSensitive) {
                $value = $row[$column['key']] ?? null;

                if (($column['sensitive'] ?? false) && ! $canSeeSensitive) {
                    return $value === null || $value === '' ? '' : self::MASK;
                }

                return $this->safe($value);
            }, $columns));
            $rows++;
        }

        fclose($handle);
        $checksum = hash_file('sha256', $absolute);
        Storage::disk($disk)->put($path, fopen($absolute, 'r'));
        unlink($absolute);

        return ['rows' => $rows, 'checksum' => $checksum];
    }

    /** A cell starting with = + - @ would run as a formula when opened in Excel. */
    private function safe(mixed $value): mixed
    {
        return is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
