<?php

namespace App\Services\Export;

use App\Contracts\Documents\PdfRenderer;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Turns the tables read from a screen into an Excel workbook (one sheet per table) or an A4 PDF.
 * Every file says who took it and when, and carries the confidentiality line the other exports carry.
 *
 * @phpstan-import-type Table from PageTables
 */
class PageExporter
{
    public function __construct(private readonly PdfRenderer $pdf, private readonly TenantContextResolver $tenant) {}

    /**
     * @param  list<Table>  $tables
     * @param  array<string, mixed>  $filters  what the person had asked for on the screen (search, dates…)
     */
    public function xlsx(string $title, array $tables, User $by, array $filters = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'export');
        $writer = new Writer;
        $writer->openToFile($path);

        $heading = (new Style)->withFontBold(true)->withFontSize(14);
        $head = (new Style)->withFontBold(true)->withBackgroundColor(Color::rgb(226, 232, 240))->withShouldWrapText(false);
        $note = (new Style)->withFontItalic(true)->withFontColor(Color::rgb(90, 90, 90));
        $names = [];

        foreach ($tables as $i => $table) {
            $sheet = $i === 0 ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
            $sheet->setName($this->sheetName($table['name'], $names));

            $meta = [$this->organisation().' — '.$title, $table['name'], $this->byLine($by), $this->filterLine($filters), __('CONFIDENTIAL — contains patient information. Do not share or forward.')];
            $metaRows = 0;
            foreach ($meta as $k => $line) {
                if ($line === '' || ($k === 1 && $table['name'] === $title)) {
                    continue;
                }
                $writer->addRow(new Row([new Cell\StringCell($line, $k === 0 ? $heading : ($k === 4 ? $note : null))]));
                $metaRows++;
            }
            $writer->addRow(new Row([]));
            $metaRows++;

            $headerRows = 0;
            if ($table['header'] !== []) {
                $writer->addRow(new Row(array_map(fn ($v) => new Cell\StringCell($v, $head), $table['header'])));
                $headerRows = 1;
            }
            foreach ($table['rows'] as $row) {
                $writer->addRow(new Row(array_map(fn ($v) => $this->cell($v), $row)));
            }

            $widths = [];
            foreach (array_merge([$table['header']], $table['rows']) as $row) {
                foreach ($row as $c => $v) {
                    $widths[$c] = max($widths[$c] ?? 8, min(60, mb_strlen($v) + 2));
                }
            }
            foreach ($widths as $c => $w) {
                $sheet->setColumnWidth((float) $w, $c + 1);
            }
            if ($headerRows === 1) {
                $sheet->setSheetView(new SheetView(freezeRow: $metaRows + 1));
            }
        }

        $writer->close();
        $bytes = (string) file_get_contents($path);
        unlink($path);

        return $bytes;
    }

    /**
     * @param  list<Table>  $tables
     * @param  array<string, mixed>  $filters
     */
    public function pdf(string $title, array $tables, User $by, array $filters = []): string
    {
        $widest = $tables === [] ? 0 : max(array_map(fn ($t) => count($t['header'] ?: ($t['rows'][0] ?? [])), $tables));
        $landscape = $widest > (int) config('export.landscape_columns', 7);

        $html = view('exports.page', [
            'organisation' => $this->organisation(),
            'branch' => $this->tenant->getBranch()?->name,
            'title' => $title,
            'tables' => $tables,
            'by' => $this->byLine($by),
            'filters' => $this->filterLine($filters),
            'landscape' => $landscape,
        ])->render();

        return $this->pdf->render($html, $landscape ? 'a4-report-landscape' : 'a4-report');
    }

    public function filename(string $title, string $extension): string
    {
        return (Str::slug($title) ?: 'export').'-'.now()->format('Ymd-Hi').'.'.$extension;
    }

    /** A plain number is a number cell; anything that could be an identifier, phone number or code stays text (and text never becomes a formula). */
    private function cell(string $v): Cell
    {
        $s = trim(str_replace('৳', '', $v));
        if (preg_match('/^-?(0|[1-9]\d{0,2}(,\d{3})+|[1-9]\d{0,11})(\.\d+)?$/', $s) === 1) {
            $plain = str_replace(',', '', $s);

            return Cell::fromValue(str_contains($plain, '.') ? (float) $plain : (int) $plain);
        }

        return new Cell\StringCell($v, null);
    }

    /** @param  list<string>  $used */
    private function sheetName(string $name, array &$used): string
    {
        $clean = trim((string) preg_replace('/[\[\]:*?\/\\\\]+/', ' ', $name)) ?: 'Sheet';
        $clean = mb_substr($clean, 0, 31);
        $base = $clean;
        for ($n = 2; in_array(mb_strtolower($clean), $used, true); $n++) {
            $clean = mb_substr($base, 0, 31 - strlen((string) $n) - 1).' '.$n;
        }
        $used[] = mb_strtolower($clean);

        return $clean;
    }

    private function organisation(): string
    {
        return $this->tenant->getOrganization()->name;
    }

    private function byLine(User $by): string
    {
        return __('Exported by :name on :when', ['name' => $by->name, 'when' => now()->format('d M Y, H:i')]);
    }

    /** @param  array<string, mixed>  $filters */
    private function filterLine(array $filters): string
    {
        $parts = [];
        foreach ($filters as $key => $value) {
            if (is_scalar($value) && (string) $value !== '' && ! in_array($key, ['page', 'export'], true)) {
                $parts[] = $key.': '.$value;
            }
        }

        return $parts === [] ? '' : __('Filters').' — '.implode(', ', $parts);
    }
}
