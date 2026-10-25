<?php

namespace Tests\Feature\Pharmacy;

use App\Contracts\Stock\StockLedger;
use App\Models\Pharmacy\PharmacyStock;
use App\Models\Pharmacy\PharmacyStockTransaction;
use App\Models\Stock\StockLedgerEntry;
use App\Services\Pharmacy\PharmacyStockService;
use App\Services\Stock\LedgerEntry;
use App\Services\Stock\StockLedgerReconciler;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

/** H-06: every pharmacy movement is also a ledger row; the ledger is append-only and always adds up to on-hand. */
class StockLedgerTest extends PharmacyTestCase
{
    public function test_every_pharmacy_movement_posts_one_ledger_row_and_sums_match_on_hand(): void
    {
        $medication = $this->makeMedication();
        $store = $this->makeStore();
        $batch = $this->receiveBatch($store, $medication, 100);
        $service = app(PharmacyStockService::class);

        $service->debit($store, $medication, $batch, 30, PharmacyStockTransaction::TYPE_DISPENSING, $this->user);
        $service->adjust($store, $batch, -5, PharmacyStockTransaction::TYPE_DAMAGE, 'dropped', $this->user);
        $service->adjust($store, $batch, 2, PharmacyStockTransaction::TYPE_CORRECTION, 'count fix', $this->user);

        $this->assertSame(4, StockLedgerEntry::where('batch_id', $batch->id)->count());
        $this->assertSame(
            ['purchase_receipt', 'dispense', 'wastage', 'adjustment_gain'],
            StockLedgerEntry::where('batch_id', $batch->id)->orderBy('id')->pluck('txn_type')->all(),
        );
        $this->assertSame(67.0, app(StockLedger::class)->onHand($this->organization->id, 'pharmacy', $store->id, $medication->id, $batch->id));
        $this->assertSame(67, PharmacyStock::where('batch_id', $batch->id)->value('quantity_available'));
        $this->assertCount(0, app(StockLedgerReconciler::class)->pharmacyDrift($this->organization->id));
    }

    public function test_the_consolidated_view_sums_the_ledger(): void
    {
        $medication = $this->makeMedication();
        $store = $this->makeStore();
        $batch = $this->receiveBatch($store, $medication, 40);
        app(PharmacyStockService::class)->debit($store, $medication, $batch, 10, 'dispensing', $this->user);

        $row = DB::table('v_stock_on_hand_all_stores')->where('batch_id', $batch->id)->first();

        $this->assertSame(30.0, (float) $row->on_hand);
    }

    public function test_ledger_rows_cannot_be_updated_or_deleted_by_the_app_or_by_raw_sql(): void
    {
        $store = $this->makeStore();
        $this->receiveBatch($store, $this->makeMedication(), 10);
        $entry = StockLedgerEntry::firstOrFail();

        try {
            $entry->update(['quantity' => 999]);
            $this->fail('model update must be refused');
        } catch (LogicException) {
            $this->assertTrue(true);
        }

        try {
            $entry->delete();
            $this->fail('model delete must be refused');
        } catch (LogicException) {
            $this->assertTrue(true);
        }

        foreach (['UPDATE stock_ledger SET quantity = 999', 'DELETE FROM stock_ledger'] as $sql) {
            try {
                DB::statement($sql);
                $this->fail("database must refuse: $sql");
            } catch (\Illuminate\Database\QueryException $e) {
                $this->assertStringContainsString('append-only', $e->getMessage());
            }
        }

        $this->assertSame(10.0, (float) StockLedgerEntry::firstOrFail()->quantity);
    }

    public function test_drift_is_detected_when_a_balance_is_changed_without_a_ledger_row(): void
    {
        $medication = $this->makeMedication();
        $store = $this->makeStore();
        $batch = $this->receiveBatch($store, $medication, 10);

        PharmacyStock::where('batch_id', $batch->id)->update(['quantity_available' => 99]); // bypasses the service

        $drift = app(StockLedgerReconciler::class)->pharmacyDrift($this->organization->id);

        $this->assertCount(1, $drift);
        $this->assertSame(99, (int) $drift->first()->on_hand);
        $this->assertSame(10.0, (float) $drift->first()->ledger);
    }

    public function test_post_is_idempotent_and_rejects_bad_entries(): void
    {
        $ledger = app(StockLedger::class);
        $make = fn (array $o = []) => new LedgerEntry(...array_merge([
            'organizationId' => $this->organization->id, 'storeKind' => 'pharmacy', 'storeId' => 1, 'itemId' => 1,
            'quantity' => 5, 'txnType' => 'purchase_receipt', 'idempotencyKey' => 'K1',
        ], $o));

        $a = $ledger->post($make());
        $b = $ledger->post($make());

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, StockLedgerEntry::where('idempotency_key', 'K1')->count());

        foreach ([['txnType' => 'teleport'], ['quantity' => 0]] as $bad) {
            try {
                $ledger->post($make($bad + ['idempotencyKey' => null]));
                $this->fail('expected rejection');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }
}
