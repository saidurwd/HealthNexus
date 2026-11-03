<?php

namespace Tests\Feature\Ui;

use App\Models\Patient;
use App\Models\PatientAllergy;
use Tests\Feature\Dashboard\FrontDoorTestCase;

/** Every ordinary screen can be printed with a letterhead and footer; forms and screens with their own print button are left alone. */
class PrintSheetTest extends FrontDoorTestCase
{
    public function test_a_page_carries_the_letterhead_footer_and_one_print_button(): void
    {
        $html = $this->as($this->actor(['rates.bed.view']))->get('/rates/beds')->assertOk()->getContent();

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

    public function test_the_wristband_shows_name_number_allergy_and_a_barcode(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        PatientAllergy::factory()->create(['organization_id' => $this->organization->id, 'patient_id' => $patient->id, 'substance' => 'Penicillin', 'severity' => 'severe', 'is_active' => true]);

        $this->as($this->actor(['patients.print']))->get('/patients/'.$patient->public_id.'/wristband')
            ->assertOk()
            ->assertSee($patient->enterprise_patient_no)
            ->assertSee('Penicillin')
            ->assertSee('<svg', false);
    }
}
