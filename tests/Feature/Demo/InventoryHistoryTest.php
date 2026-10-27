<?php

namespace Tests\Feature\Demo;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Organization;
use App\Services\Inventory\InventoryLedgerReconciler;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SettingsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** The six-month stores-and-purchasing history on its own (the full history runs in DemoHistoryTest, one month at a time). */
class InventoryHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_six_months_of_stores_and_purchasing_hang_together(): void
    {
        $organization = Organization::factory()->create(['code' => 'INVH']);
        Branch::factory()->create(['organization_id' => $organization->id]);
        foreach (['Surgery', 'Medicine', 'Casualty', 'Pathology'] as $name) {
            Department::factory()->create(['organization_id' => $organization->id, 'name' => $name]);
        }
        $this->seed([PermissionSeeder::class, SettingsTableSeeder::class]);

        $this->assertSame(0, Artisan::call('demo:history', ['organization' => 'INVH', '--only' => ['InventoryHistory'], '--password' => 'demo-pass-123']));
        $output = Artisan::output();
        getenv('DEMO_VERBOSE') && fwrite(STDERR, $output);
        $org = $organization->id;

        $this->assertStringContainsString('Stores and purchasing', $output);
        $has = fn (string $table, int $min, array $where = []) => $this->assertGreaterThanOrEqual($min, DB::table($table)->where('organization_id', $org)->where($where)->count(), "$table has history");
        $has('inventory_items', 25);
        $has('procurement_suppliers', 8);
        $has('procurement_purchase_orders', 20);
        $has('procurement_goods_receipts', 20, ['status' => 'posted']);
        $has('procurement_supplier_invoices', 10);
        $has('inventory_issues', 10);
        $has('inventory_transfers', 10, ['status' => 'received']);
        $has('inventory_counts', 3, ['status' => 'approved']);
        $has('inventory_adjustments', 1, ['status' => 'approved']);
        $has('procurement_approvals', 20, ['status' => 'approved']);
        $has('inventory_recalls', 1);

        // the story is told in the right states
        $this->assertGreaterThanOrEqual(1, DB::table('procurement_purchase_orders')->where('organization_id', $org)->where('po_type', 'emergency')->whereNotNull('ratified_at')->count(), 'one emergency order was approved afterwards');
        $this->assertGreaterThanOrEqual(1, DB::table('procurement_purchase_orders')->where('organization_id', $org)->where('po_type', 'emergency')->whereNull('ratified_at')->count(), 'one is still waiting');
        $this->assertGreaterThanOrEqual(1, DB::table('procurement_supplier_invoices')->where('organization_id', $org)->whereIn('status', ['approved', 'rejected'])->count(), 'a mismatched invoice was decided');
        $this->assertGreaterThanOrEqual(1, DB::table('procurement_suppliers')->where('organization_id', $org)->where('status', 'disqualified')->count());

        // and it adds up
        $this->assertSame(0, app(InventoryLedgerReconciler::class)->drift($org)->count(), 'inventory stock equals the ledger');
        $this->assertSame(0, DB::table('inventory_stock')->where('organization_id', $org)->where('quantity_on_hand', '<', 0)->count(), 'no negative stock');
        $this->assertSame(0, DB::table('procurement_approvals as a')->join('procurement_purchase_orders as o', function ($j) {
            $j->on('o.id', '=', 'a.document_id')->where('a.document_type', 'purchase_order');
        })->where('a.status', 'approved')->whereColumn('a.decided_by', 'o.created_by')->count(), 'nobody approved their own order');
        $this->assertSame(0, DB::table('procurement_supplier_invoices')->where('organization_id', $org)->where('status', 'mismatch')->whereNotNull('finance_reference')->count(), 'no mismatched invoice reached Finance');
        $this->assertSame(0, DB::table('inventory_stock as s')->join('inventory_batches as b', 'b.id', '=', 's.batch_id')->join('inventory_stores as st', 'st.id', '=', 's.store_id')
            ->where('s.organization_id', $org)->where('s.quantity_on_hand', '>', 0)->where('st.kind', '!=', 'quarantine')->whereIn('b.status', ['quarantined', 'recalled'])->count(), 'held or recalled stock sits only in quarantine');
        $this->assertSame(0, DB::table('procurement_po_lines as l')->join('procurement_purchase_orders as o', 'o.id', '=', 'l.purchase_order_id')->where('o.organization_id', $org)->whereColumn('l.received_quantity', '>', 'l.quantity')->count(), 'no line was over-received');
    }
}
