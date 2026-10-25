<?php

namespace App\Services\Reports;

use App\Contracts\Reports\ReportDefinition;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/** Streams rows to an .xlsx file (openspout keeps memory flat). Same masking, watermark and formula protection as the CSV writer. */
class XlsxExportWriter
{
    private const MASK = '***';

    /** @return array{rows: int, checksum: string} */
    public function write(ReportDefinition $definition, ReportContext $context, string $disk, string $path): array
    {
        $absolute = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer = new Writer;
        $writer->openToFile($absolute);

        $columns = $definition->columns();
        $canSeeSensitive = $definition->sensitivePermission() !== null && $context->user->can($definition->sensitivePermission());

        $writer->addRow($this->row([$definition->title().' — exported by '.$context->user->name.' (user '.$context->user->id.') on '.now()->toDateTimeString().' UTC']));
        $writer->addRow($this->row(['CONFIDENTIAL — contains patient information. Do not share or forward.']));
        $writer->addRow($this->row(array_column($columns, 'label')));

        $rows = 0;
        foreach ($definition->rows($context) as $row) {
            $writer->addRow($this->row(array_map(function (array $column) use ($row, $canSeeSensitive) {
                $value = $row[$column['key']] ?? null;

                if (($column['sensitive'] ?? false) && ! $canSeeSensitive) {
                    return $value === null || $value === '' ? '' : self::MASK;
                }

                return $value;
            }, $columns)));
            $rows++;
        }

        $writer->close();
        $checksum = hash_file('sha256', $absolute);
        Storage::disk($disk)->put($path, fopen($absolute, 'r'));
        unlink($absolute);

        return ['rows' => $rows, 'checksum' => $checksum];
    }

    /** Text is ALWAYS a string cell: OpenSpout would otherwise turn a value starting with "=" into a live formula. */
    private function row(array $values): Row
    {
        return new Row(array_map(fn ($v) => is_string($v) ? new Cell\StringCell($v, null) : Cell::fromValue($v), $values));
    }
}
