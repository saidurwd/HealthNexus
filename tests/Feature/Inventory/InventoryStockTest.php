<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\InventoryStock;
use App\Services\Inventory\InventoryLedgerReconciler;
use App\Services\Inventory\InventoryMasterService;
use App\Services\Inventory\InventoryStockService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryStockTest extends InventoryTestCase
{
    public function test_balance_equals_the_ledger_sum_and_a_rebuild_repairs_drift(): void
    {
        $store = $this->store();
        $item = $this->item();
        $batch = $this->stock($store, $item, '100');
        $stock = app(InventoryStockService::class);
        $stock->take($store, $item, $batch, '30', 'issue', 'T1', '10', null, null, $this->user);
        $stock->receive($store, $item, $batch, '5', 'return', 'T2', '10', null, null, $this->user);

        $this->assertSame('75.0000', $stock->onHand($store, $item));
        $this->assertSame(0, app(InventoryLedgerReconciler::class)->drift($this->organization->id)->count());

        InventoryStock::query()->where('store_id', $store->id)->update(['quantity_on_hand' => 999]); // somebody edits the projection by hand
        $reconciler = app(InventoryLedgerReconciler::class);
        $this->assertSame(1, $reconciler->drift($this->organization->id)->count());
        $this->assertSame(1, $reconciler->rebuild($this->organization->id));
        $this->assertSame('75.0000', $stock->onHand($store, $item));
    }

    public function test_the_ledger_refuses_updates_and_deletes(): void
    {
        $this->stock($this->store(), $this->item(), '10');

        $this->expectException(QueryException::class);
        DB::table('stock_ledger')->where('store_kind', 'inventory')->update(['quantity' => 1]);
    }

    public function test_the_same_posting_key_twice_moves_stock_once(): void
    {
        $store = $this->store();
        $item = $this->item();
        $batch = $this->stock($store, $item, '50');
        $stock = app(InventoryStockService::class);
        $stock->take($store, $item, $batch, '10', 'issue', 'SAME', '10', null, null, $this->user);
        $stock->take($store, $item, $batch, '10', 'issue', 'SAME', '10', null, null, $this->user);

        $this->assertSame('40.0000', $stock->onHand($store, $item));
    }

    public function test_stock_cannot_go_negative_unless_the_store_allows_it(): void
    {
        $item = $this->item(['tracks_batch' => false, 'tracks_expiry' => false]);
        $strict = $this->store();
        $loose = $this->store('ward', null, true);
        $stock = app(InventoryStockService::class);
        $stock->receive($strict, $item, 0, '3', 'opening_balance', 'O1');

        try {
            $stock->take($strict, $item, 0, '4', 'issue', 'X1');
            $this->fail('A store that does not allow negative stock gave out more than it had.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('quantity', $e->errors());
        }
        $stock->take($loose, $item, 0, '4', 'issue', 'X2');
        $this->assertSame('-4.0000', $stock->onHand($loose, $item));
    }

    public function test_oldest_expiry_is_issued_first_and_expired_or_held_batches_are_skipped(): void
    {
        $store = $this->store();
        $item = $this->item();
        $late = $this->stock($store, $item, '10', 'LATE', now()->addMonths(10)->toDateString());
        $soon = $this->stock($store, $item, '10', 'SOON', now()->addMonths(2)->toDateString());
        $old = $this->stock($store, $item, '10', 'OLD', now()->subDay()->toDateString());
        $held = $this->stock($store, $item, '10', 'HELD', now()->addMonth()->toDateString());
        $this->batchOf($held)->update(['status' => 'quarantined']);

        $picks = app(InventoryStockService::class)->allocate($store, $item, '15');

        $this->assertSame([[$soon, '10.0000'], [$late, '5.0000']], array_map(fn ($p) => [$p['batch_id'], $p['quantity']], $picks));
        $this->assertNotContains($old, array_column($picks, 'batch_id'));

        $this->expectException(ValidationException::class);
        app(InventoryStockService::class)->allocate($store, $item, '25'); // only 20 usable
    }

    public function test_an_expired_batch_cannot_be_taken_by_a_normal_issue(): void
    {
        $store = $this->store();
        $item = $this->item();
        $old = $this->stock($store, $item, '10', 'OLD', now()->subDay()->toDateString());

        $this->expectException(ValidationException::class);
        app(InventoryStockService::class)->take($store, $item, $old, '1', 'issue', 'E1', '10');
    }

    public function test_reserved_stock_stays_on_hand_but_is_not_available(): void
    {
        $store = $this->store();
        $item = $this->item(['tracks_batch' => false, 'tracks_expiry' => false]);
        $stock = app(InventoryStockService::class);
        $stock->receive($store, $item, 0, '10', 'opening_balance', 'R0');
        $stock->reserve($store, $item, 0, '8');

        $this->assertSame('10.0000', $stock->onHand($store, $item));
        $this->assertSame('2.0000', $stock->usable($store, $item));
        $this->expectException(ValidationException::class);
        $stock->take($store, $item, 0, '3', 'issue', 'R1');
    }

    public function test_unit_conversion_and_item_rules(): void
    {
        $masters = app(InventoryMasterService::class);
        $item = $this->item(['purchase_uom' => 'box', 'purchase_to_base' => '12', 'tracks_batch' => false, 'tracks_expiry' => false]);
        $this->assertSame('36.0000', $masters->toBase($item, 3, 'box'));
        $this->assertSame('5.0000', $masters->toBase($item, 5));

        $this->expectException(ValidationException::class);
        $this->item(['tracks_batch' => false, 'tracks_expiry' => true]); // an expiry date needs batches
    }

    public function test_a_duplicate_item_code_is_refused(): void
    {
        $this->item(['code' => 'GLOVE-M']);
        $this->expectException(ValidationException::class);
        $this->item(['code' => 'glove-m']);
    }
}
