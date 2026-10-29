<?php

namespace Tests\Feature\Analytics;

use App\Services\Analytics\LoadRunner;
use App\Services\Analytics\Warehouse;
use Tests\Feature\Finance\FinanceTestCase;

/** Hospital, branch, patients, invoices (Finance/Portal fixtures) plus the warehouse on the application's own database. */
abstract class AnalyticsTestCase extends FinanceTestCase
{
    protected function runLoads(?array $only = null): array
    {
        return app(LoadRunner::class)->run($only);
    }

    protected function wh(string $table)
    {
        return app(Warehouse::class)->table($table);
    }
}
