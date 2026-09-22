<?php

namespace Tests\Feature\Billing;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Regression test for a route-ordering bug: Laravel matches routes in registration order, so a
 * wildcard show route like GET invoices/{invoice} registered before GET invoices/create will
 * swallow "create" as the {invoice} parameter and 404 on model binding. Every "create" page
 * that sits alongside a wildcard "show" route on the same literal prefix is checked here.
 */
class BillingCreatePageRoutingTest extends BillingTestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function createPageProvider(): array
    {
        return [
            'invoices' => ['/admin/billing/invoices/create'],
            'items' => ['/admin/billing/items/create'],
            'price-lists' => ['/admin/billing/price-lists/create'],
            'payments' => ['/admin/billing/payments/create'],
            'corporates' => ['/admin/billing/corporates/create'],
        ];
    }

    #[DataProvider('createPageProvider')]
    public function test_create_page_does_not_404_behind_a_wildcard_show_route(string $path): void
    {
        $response = $this->get($path);

        $response->assertOk();
    }
}
