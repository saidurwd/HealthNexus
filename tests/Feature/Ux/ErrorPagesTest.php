<?php

namespace Tests\Feature\Ux;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_a_missing_page_uses_the_product_error_screen_with_a_reference(): void
    {
        $response = $this->withHeader('X-Request-Id', 'ref-test-0001')->get('/definitely/missing/page');

        $response->assertNotFound();
        $response->assertSee('bg-body-tertiary', false);
        $response->assertSee('ref-test-0001');
        $response->assertSee('Tell support this reference');
    }
}
