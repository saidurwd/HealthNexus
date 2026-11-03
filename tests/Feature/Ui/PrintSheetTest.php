<?php

namespace Tests\Feature\Ui;

use Tests\Feature\Dashboard\FrontDoorTestCase;

/** Every ordinary screen can be printed with a letterhead and footer; forms and screens with their own print button are left alone. */
class PrintSheetTest extends FrontDoorTestCase
{
    public function test_a_page_carries_the_letterhead_footer_and_one_print_button(): void
    {
        $html = $this->as($this->actor(['rates.bed.view']))->get('/admin/rates/beds')->assertOk()->getContent();

        $this->assertStringContainsString('css/print.css', $html);
        $this->assertStringContainsString('print-letterhead', $html);
        $this->assertStringContainsString('print-footer', $html);
        $this->assertStringContainsString('id="auto-print"', $html);
    }

    public function test_the_print_sheet_hides_menus_and_buttons_and_repeats_table_headings(): void
    {
        $css = file_get_contents(public_path('css/print.css'));

        foreach (['.app-sidebar', '.btn', 'table-header-group', '.print-footer', '@page'] as $needle) {
            $this->assertStringContainsString($needle, $css);
        }
    }
}
