<?php

namespace Tests\Feature\Pharmacy;

use App\Services\Pharmacy\PharmacyBatchSelectionService;
use Illuminate\Validation\ValidationException;

/**
 * Proves PharmacyBatchSelectionService's FEFO (First-Expiry-First-Out) allocation: eligible
 * batches are ordered by expiry_date ASC and allocated greedily; quarantined/expired batches are
 * excluded from the pool; insufficient stock throws rather than silently partial-allocating.
 */
class FefoBatchSelectionTest extends PharmacyTestCase
{
    public function test_earliest_expiring_batch_is_allocated_first(): void
    {
        $medication = $this->makeMedication();
        $store = $this->makeStore();

        $laterBatch = $this->receiveBatch($store, $medication, 50, now()->addMonths(6)->toDateString());
        $earlierBatch = $this->receiveBatch($store, $medication, 30, now()->addMonths(2)->toDateString());

        $allocations = app(PharmacyBatchSelectionService::class)->selectForDispense($store, $medication, 20);

        $this->assertCount(1, $allocations);
        $this->assertSame($earlierBatch->id, $allocations->first()['stock']->batch_id);
        $this->assertSame(20, $allocations->first()['quantity']);
    }

    public function test_allocation_spans_multiple_batches_when_one_is_insufficient(): void
    {
        $medication = $this->makeMedication();
        $store = $this->makeStore();

        $earlierBatch = $this->receiveBatch($store, $medication, 10, now()->addMonths(1)->toDateString());
        $laterBatch = $this->receiveBatch($store, $medication, 50, now()->addMonths(6)->toDateString());

        $allocations = app(PharmacyBatchSelectionService::class)->selectForDispense($store, $medication, 15);

        $this->assertCount(2, $allocations);
        $this->assertSame($earlierBatch->id, $allocations[0]['stock']->batch_id);
        $this->assertSame(10, $allocations[0]['quantity']);
        $this->assertSame($laterBatch->id, $allocations[1]['stock']->batch_id);
        $this->assertSame(5, $allocations[1]['quantity']);
    }

    public function test_quarantined_batches_are_excluded_from_fefo_pool(): void
    {
        $medication = $this->makeMedication();
        $store = $this->makeStore();

        $batch = $this->receiveBatch($store, $medication, 30, now()->addMonths(3)->toDateString());
        $batch->update(['is_quarantined' => true]);

        $this->expectException(ValidationException::class);

        app(PharmacyBatchSelectionService::class)->selectForDispense($store, $medication, 5);
    }

    public function test_expired_batches_are_excluded_from_fefo_pool(): void
    {
        $medication = $this->makeMedication();
        $store = $this->makeStore();

        $this->receiveBatch($store, $medication, 30, now()->subDays(1)->toDateString());

        $this->expectException(ValidationException::class);

        app(PharmacyBatchSelectionService::class)->selectForDispense($store, $medication, 5);
    }

    public function test_insufficient_stock_throws_rather_than_partial_allocating(): void
    {
        $medication = $this->makeMedication();
        $store = $this->makeStore();

        $this->receiveBatch($store, $medication, 5, now()->addMonths(3)->toDateString());

        $this->expectException(ValidationException::class);

        app(PharmacyBatchSelectionService::class)->selectForDispense($store, $medication, 10);
    }
}
