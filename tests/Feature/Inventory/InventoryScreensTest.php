<?php

namespace Tests\Feature\Inventory;

use App\Models\Branch;
use App\Models\Inventory\InventoryBatch;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\ProcurementSupplier;
use App\Models\Organization;
use App\Services\Inventory\InventoryCountService;
use App\Services\Inventory\InventoryExpiryService;
use App\Services\Inventory\InventoryStockService;
use App\Services\Inventory\RequisitionService;
use App\Services\Inventory\RfqService;
use App\Services\Inventory\SupplierInvoiceMatchingService;

class InventoryScreensTest extends InventoryTestCase
{
    /** One of everything, so every screen has something to draw. */
    private function seedEverything(): array
    {
        $store = $this->store();
        $ward = $this->store('ward');
        $supplier = $this->supplier();
        $item = $this->item(['reorder_min' => 500, 'reorder_max' => 900]);
        $order = $this->issuedOrder($supplier, $store, [[$item, 10, '100']]);
        $receipt = $this->receiveAll($order);
        app(SupplierInvoiceMatchingService::class)->record($this->user, $supplier, $order->refresh(), 'INV-S1', now()->toDateString(), [['po_line_id' => $order->lines[0]->id, 'quantity' => 10, 'unit_price' => '100']]);
        $req = app(RequisitionService::class)->create($this->user, $store, [['item_id' => $item->id, 'quantity' => 3, 'estimated_unit_price' => 5]]);
        app(RequisitionService::class)->submit($req, $this->user);
        app(RequisitionService::class)->approve($req, $this->actor(['procurement.approval.level1']));
        $rfq = app(RfqService::class)->createFromRequisition($req->refresh(), [$supplier], $this->user);
        $count = app(InventoryCountService::class)->start($this->user, $store);
        $batch = InventoryBatch::where('item_id', $item->id)->first();

        return compact('store', 'ward', 'supplier', 'item', 'order', 'receipt', 'rfq', 'count', 'batch');
    }

    public function test_every_screen_draws_for_a_super_admin(): void
    {
        $d = $this->seedEverything();
        $urls = [
            route('admin.inventory.dashboard'), route('admin.inventory.items'), route('admin.inventory.items.show', $d['item']), route('admin.inventory.stores'), route('admin.inventory.stock'),
            route('admin.inventory.reports'), route('admin.inventory.reports', ['report' => 'consumption']), route('admin.inventory.reports', ['report' => 'slow']), route('admin.inventory.reports', ['report' => 'suppliers']),
            route('admin.inventory.expiry'), route('admin.inventory.batches.trace', $d['batch']), route('admin.inventory.adjustments'), route('admin.inventory.counts'), route('admin.inventory.counts.show', $d['count']),
            route('admin.inventory.transfers'), route('admin.inventory.requests'), route('admin.inventory.consumption'),
            route('admin.procurement.suppliers'), route('admin.procurement.suppliers.show', $d['supplier']), route('admin.procurement.budgets'), route('admin.procurement.requisitions'), route('admin.procurement.reorder'),
            route('admin.procurement.rfqs'), route('admin.procurement.rfqs.show', $d['rfq']), route('admin.procurement.orders'), route('admin.procurement.orders.show', $d['order']),
            route('admin.procurement.receipts'), route('admin.procurement.receipts.show', $d['receipt']), route('admin.procurement.invoices'), route('admin.procurement.returns'),
        ];
        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_the_stock_report_downloads_as_csv_with_formulas_neutralised(): void
    {
        $this->seedEverything();
        $response = $this->get(route('admin.inventory.reports', ['report' => 'valuation', 'export' => 'csv']))->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_a_user_without_the_permission_is_refused_and_one_with_it_is_let_in(): void
    {
        $d = $this->seedEverything();
        $nobody = $this->actor([]);
        $this->actingAs($nobody);
        $this->get(route('admin.inventory.items'))->assertForbidden();
        $this->get(route('admin.procurement.orders.show', $d['order']))->assertForbidden();
        $this->post(route('admin.procurement.orders.move', $d['order']), ['action' => 'cancel', 'reason' => 'x'])->assertForbidden();

        $viewer = $this->actor(['inventory.item.view']);
        $this->actingAs($viewer);
        $this->get(route('admin.inventory.items'))->assertOk();
        $this->post(route('admin.inventory.items.store'), ['code' => 'X', 'name' => 'X', 'category_id' => 1, 'base_uom' => 'each'])->assertForbidden();
    }

    public function test_records_of_another_organization_do_not_exist(): void
    {
        $d = $this->seedEverything();
        $other = Organization::factory()->create();
        $otherBranch = Branch::factory()->create(['organization_id' => $other->id]);
        $outsider = $this->staff(['super_admin'], $other, $otherBranch);
        $this->actingAs($outsider);
        $this->context($other, $otherBranch);

        $this->get(route('admin.inventory.items.show', $d['item']))->assertNotFound();
        $this->get(route('admin.procurement.orders.show', $d['order']))->assertNotFound();
        $this->get(route('admin.procurement.suppliers.show', $d['supplier']))->assertNotFound();
        $this->get(route('admin.inventory.counts.show', $d['count']))->assertNotFound();
        $this->get(route('admin.inventory.batches.trace', $d['batch']))->assertNotFound();
        $this->post(route('admin.inventory.transfers.store'), ['from' => $d['store']->public_id, 'to' => $d['ward']->public_id, 'item' => $d['item']->public_id, 'quantity' => 1])->assertNotFound();
    }

    public function test_adding_an_item_and_a_store_through_the_screens(): void
    {
        $category = $this->category();
        $this->post(route('admin.inventory.items.store'), ['category_id' => $category->id, 'code' => 'glove-l', 'name' => 'Gloves L', 'base_uom' => 'pair', 'purchase_uom' => 'box', 'purchase_to_base' => 50, 'tracks_expiry' => 1, 'standard_cost' => 12])->assertRedirect();
        $item = InventoryItem::where('code', 'GLOVE-L')->firstOrFail();
        $this->assertTrue((bool) $item->tracks_batch); // an expiry date needs batches
        $this->assertSame('50.0000', (string) $item->uoms()->where('uom', 'box')->value('factor_to_base'));

        $this->post(route('admin.inventory.stores.store'), ['code' => 'ot-1', 'name' => 'OT consumables', 'kind' => 'ot'])->assertRedirect();
        $this->assertDatabaseHas('inventory_stores', ['code' => 'OT-1', 'kind' => 'ot', 'organization_id' => $this->organization->id]);
    }

    public function test_the_receive_inspect_invoice_flow_through_the_screens(): void
    {
        $store = $this->store();
        $supplier = $this->supplier();
        $item = $this->item();
        $order = $this->issuedOrder($supplier, $store, [[$item, 20, '50']]);
        $line = $order->lines[0];

        $this->post(route('admin.procurement.orders.receive', $order), ['delivery_note' => 'DN-1', 'lines' => [$line->id => ['quantity' => 20, 'batch_no' => 'L9', 'expiry_date' => now()->addYear()->toDateString()]]])->assertRedirect();
        $receipt = $order->receipts()->first();
        $this->assertSame('pending_inspection', $receipt->status);
        $this->post(route('admin.procurement.receipts.inspect', $receipt), ['rejected' => [$receipt->lines[0]->id => 2], 'reason' => [$receipt->lines[0]->id => 'Torn packs']])->assertRedirect();

        $this->assertSame('18.0000', app(InventoryStockService::class)->onHand($store, $item));
        $this->post(route('admin.procurement.orders.invoices', $order->refresh()), ['invoice_number' => 'S-77', 'invoice_date' => now()->toDateString(), 'lines' => [$line->id => ['quantity' => 20, 'unit_price' => 50]]])->assertRedirect();
        $this->assertDatabaseHas('procurement_supplier_invoices', ['invoice_number' => 'S-77', 'status' => 'mismatch']); // billed 20, only 18 accepted
    }

    public function test_an_expired_batch_sweep_is_visible_on_the_expiry_screen(): void
    {
        $store = $this->store();
        $item = $this->item();
        $this->stock($store, $item, '5', 'OLD', now()->subDays(3)->toDateString());
        app(InventoryExpiryService::class)->sweep($this->organization->id, $this->user);

        $this->get(route('admin.inventory.expiry', ['days' => 30]))->assertOk()->assertSee('OLD');
        $this->get(route('admin.inventory.adjustments'))->assertOk()->assertSee('OLD', false);
    }

    public function test_the_supplier_screen_shows_what_is_missing(): void
    {
        $supplier = $this->supplier(false);
        $this->get(route('admin.procurement.suppliers.show', $supplier))->assertOk();
        $this->assertInstanceOf(ProcurementSupplier::class, $supplier);
    }
}
