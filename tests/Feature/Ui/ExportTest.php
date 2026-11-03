<?php

namespace Tests\Feature\Ui;

use App\Contracts\Documents\PdfRenderer;
use App\Models\AuditLog;
use App\Models\Provider;
use App\Services\Export\PageTables;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;
use Tests\Feature\Dashboard\FrontDoorTestCase;

/** Any staff screen can be exported to Excel or PDF with ?export=; the file holds the tables the screen shows. */
class ExportTest extends FrontDoorTestCase
{
    private function providers(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Provider::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'name' => sprintf('Dr Export %02d', $i)]);
        }
    }

    private function sheetRows(string $bytes): array
    {
        $path = tempnam(sys_get_temp_dir(), 'x').'.xlsx';
        file_put_contents($path, $bytes);
        $reader = new Reader;
        $reader->open($path);
        $out = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $out[$sheet->getName()][] = array_map(fn ($c) => $c->getValue(), $row->cells);
            }
        }
        $reader->close();
        unlink($path);

        return $out;
    }

    public function test_the_reader_keeps_words_and_numbers_and_drops_buttons_and_empty_columns(): void
    {
        $html = '<div class="app-content"><div class="card"><div class="card-header"><h3 class="card-title">Stock</h3></div><table><thead><tr><th>Item</th><th>Qty</th><th>Actions</th></tr></thead>'
            .'<tbody><tr><td>Gauze <span class="btn">x</span></td><td>1,250</td><td><a class="btn">View</a></td></tr><tr><td colspan="3">Nothing</td></tr></tbody></table></div></div>';

        $read = (new PageTables)->read($html);

        $this->assertSame('Stock', $read['tables'][0]['name']);
        $this->assertSame(['Item', 'Qty'], $read['tables'][0]['header']);
        $this->assertSame([['Gauze', '1,250']], $read['tables'][0]['rows']);
    }

    public function test_excel_export_has_the_rows_follows_pages_and_is_audited(): void
    {
        $this->providers(25); // the list shows 20 a page
        $me = $this->actor(['appointments.manage_provider']);

        $response = $this->as($me)->get('/providers?export=xlsx');

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $names = collect($this->sheetRows($response->getContent()))->flatten(1)->flatten()->filter(fn ($v) => is_string($v) && str_starts_with($v, 'Dr Export'))->values();
        $this->assertCount(25, $names, 'both pages are in the workbook');
        $this->assertTrue(AuditLog::query()->where('action', 'EXPORT')->exists());
    }

    public function test_pdf_export_is_rendered_with_the_title_and_rows(): void
    {
        $this->providers(3);
        $seen = new \stdClass;
        $this->app->instance(PdfRenderer::class, new class($seen) implements PdfRenderer
        {
            public function __construct(private \stdClass $seen) {}

            public function render(string $html, string $paper): string
            {
                $this->seen->html = $html;
                $this->seen->paper = $paper;

                return '%PDF-fake';
            }
        });

        $response = $this->as($this->actor(['appointments.manage_provider']))->get('/providers?export=pdf');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Dr Export 01', $seen->html);
        $this->assertStringContainsString('CONFIDENTIAL', $seen->html);
        $this->assertStringStartsWith('a4-report', $seen->paper);
    }

    public function test_a_person_without_the_right_gets_the_refusal_not_a_file(): void
    {
        $this->providers(1);

        $this->as($this->actor([]))->get('/providers?export=xlsx')->assertForbidden();
    }

    public function test_screens_with_secrets_are_never_exported_and_an_unknown_format_is_ignored(): void
    {
        $me = $this->actor(['appointments.manage_provider']);

        $this->as($me)->get('/providers?export=csv')->assertOk()->assertHeader('Content-Type', 'text/html; charset=utf-8');
        $this->assertTrue(Str::is((array) config('export.except'), 'admin.profile.mfa.setup'));
    }
}
