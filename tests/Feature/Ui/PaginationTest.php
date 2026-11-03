<?php

namespace Tests\Feature\Ui;

use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/** Page links use the Bootstrap look the screens are built in, not the Tailwind default. */
class PaginationTest extends TestCase
{
    public function test_page_links_render_as_bootstrap(): void
    {
        $html = (string) (new LengthAwarePaginator(range(1, 10), 50, 10, 2, ['path' => '/x']))->links();

        $this->assertStringContainsString('class="pagination"', $html);
        $this->assertStringContainsString('page-item', $html);
        $this->assertStringNotContainsString('tailwind', strtolower($html));
    }
}
