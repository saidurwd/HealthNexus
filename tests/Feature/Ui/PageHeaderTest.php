<?php

namespace Tests\Feature\Ui;

use App\Services\Breadcrumbs;
use Illuminate\Http\Request;
use Tests\Feature\Dashboard\FrontDoorTestCase;

/** One title, and a trail read from the sidebar: Dashboard > section > group > entry. */
class PageHeaderTest extends FrontDoorTestCase
{
    private function trail(string $path, ?string $title = null): array
    {
        return array_column((new Breadcrumbs(Request::create($path)))->generate($title), 'text');
    }

    public function test_a_menu_page_is_placed_under_its_section_and_group(): void
    {
        $this->assertSame(['Dashboard', 'Front desk', 'Patients', 'Register Patient'], $this->trail('/patients/create'));
    }

    public function test_a_deeper_page_adds_its_own_title_after_the_menu_entry(): void
    {
        $this->assertSame(['Dashboard', 'Front desk', 'Patients', 'All Patients', 'Jane Doe'], $this->trail('/patients/ABC123', 'Jane Doe'));
    }

    public function test_the_dashboard_is_just_the_dashboard(): void
    {
        $this->assertSame(['Dashboard'], $this->trail('/dashboard'));
    }

    public function test_a_page_renders_with_a_single_title_and_the_trail(): void
    {
        $html = $this->as($this->actor(['rates.bed.view']))->get('/rates/beds')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'id="page-title"'));
        $this->assertStringContainsString('breadcrumb-item', $html);
        $this->assertStringContainsString('breadcrumb small mb-1 justify-content-end', $html);
        $this->assertStringContainsString('id="page-actions"', $html);
    }
}
