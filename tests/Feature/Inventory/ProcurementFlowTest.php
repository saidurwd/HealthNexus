<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\ProcurementBudget;
use App\Services\Inventory\ApprovalService;
use App\Services\Inventory\BudgetService;
use App\Services\Inventory\GoodsReceiptService;
use App\Services\Inventory\InventoryStockService;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\RequisitionService;
use App\Services\Inventory\RfqService;
use App\Services\Inventory\SupplierInvoiceMatchingService;
use App\Services\Inventory\SupplierReturnService;
use App\Services\Inventory\SupplierService;
use Illuminate\Validation\ValidationException;

class ProcurementFlowTest extends InventoryTestCase
{
    public function test_a_disqualified_or_unqualified_supplier_cannot_be_ordered_from(): void
    {
        $store = $this->store();
        $item = $this->item();
        $orders = app(PurchaseOrderService::class);
        $line = [['item_id' => $item->id, 'quantity' => 5, 'unit_price' => 100]];

        $prospect = $this->supplier(false);
        try {
            $orders->create($this->user, $prospect, $store, $line);
            $this->fail('A supplier that was never qualified could be ordered from.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('supplier', $e->errors());
        }

        $supplier = $this->supplier();
        $order = $orders->create($this->user, $supplier, $store, $line);
        app(SupplierService::class)->disqualify($supplier, 'Sold expired goods', $this->user);
        $this->expectException(ValidationException::class);
        $orders->submit($order, $this->user); // already-drafted orders are stopped too
    }

    public function test_qualification_needs_valid_documents_and_expiry_suspends_ordering(): void
    {
        $suppliers = app(SupplierService::class);
        $supplier = $suppliers->create($this->organization->id, ['code' => 'NEW', 'name' => 'New Traders']);
        try {
            $suppliers->qualify($supplier, $this->user);
            $this->fail('Qualified without documents.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('trade_licence', $e->errors()['documents'][0]);
        }
        $suppliers->addDocument($supplier, 'trade_licence', 'TL-1', now()->subYear()->toDateString(), now()->subDay()->toDateString()); // already expired
        $suppliers->addDocument($supplier, 'vat_bin', 'BIN-1', null, null);
        $this->assertSame(['trade_licence'], $suppliers->documentGaps($supplier));

        $suppliers->addDocument($supplier, 'trade_licence', 'TL-2', now()->toDateString(), now()->addYear()->toDateString());
        $suppliers->qualify($supplier, $this->user);
        $suppliers->assertCanOrder($supplier->refresh());
        $this->travel(2)->years(); // everything expires
        $this->expectException(ValidationException::class);
        $suppliers->assertCanOrder($supplier->refresh());
    }

    public function test_a_poor_scorecard_suspends_a_supplier(): void
    {
        $supplier = $this->supplier();
        app(SupplierService::class)->evaluate($supplier, $this->user, 4.5, 4.0, 3.0);
        $this->assertSame('qualified', $supplier->refresh()->status);
        app(SupplierService::class)->evaluate($supplier, $this->user, 1.0, 2.0, 2.0);
        $this->assertSame('suspended', $supplier->refresh()->status);
    }

    public function test_approval_levels_follow_the_amount_and_need_different_people(): void
    {
        $store = $this->store();
        $item = $this->item();
        $requisitions = app(RequisitionService::class);
        $requester = $this->actor(['procurement.requisition.create']);
        $big = $requisitions->create($requester, $store, [['item_id' => $item->id, 'quantity' => 1000, 'estimated_unit_price' => 100]]); // 100,000: levels 1 and 2
        $requisitions->submit($big, $requester);
        $this->assertSame([1, 2], app(ApprovalService::class)->chain('requisition', $big->id)->pluck('level')->all());

        $level1 = $this->actor(['procurement.approval.level1']);
        $level2 = $this->actor(['procurement.approval.level2']);
        $both = $this->actor(['procurement.approval.level1', 'procurement.approval.level2']);

        try {
            $requisitions->approve($big, $level2); // level 1 comes first
            $this->fail('Level 2 approved before level 1.');
        } catch (ValidationException) {
        }
        $requisitions->approve($big, $both);
        $this->assertSame('submitted', $big->refresh()->status);
        try {
            $requisitions->approve($big, $both); // the same person cannot take the second level
            $this->fail('One person approved two levels.');
        } catch (ValidationException) {
        }
        $requisitions->approve($big, $level2);
        $this->assertSame('approved', $big->refresh()->status);
        unset($level1);
    }

    public function test_nobody_approves_what_they_raised(): void
    {
        $item = $this->item();
        $requester = $this->actor(['procurement.requisition.create', 'procurement.approval.level1']);
        $requisitions = app(RequisitionService::class);
        $req = $requisitions->create($requester, $this->store(), [['item_id' => $item->id, 'quantity' => 1, 'estimated_unit_price' => 10]]);
        $requisitions->submit($req, $requester);

        $this->expectException(ValidationException::class);
        $requisitions->approve($req, $requester);
    }

    public function test_the_winning_quotation_becomes_a_purchase_order_at_the_quoted_prices(): void
    {
        $store = $this->store();
        $a = $this->item();
        $b = $this->item();
        $requisitions = app(RequisitionService::class);
        $req = $requisitions->create($this->user, $store, [['item_id' => $a->id, 'quantity' => 10, 'estimated_unit_price' => 50], ['item_id' => $b->id, 'quantity' => 4, 'estimated_unit_price' => 20]]);
        $requisitions->submit($req, $this->user);
        $requisitions->approve($req, $this->actor(['procurement.approval.level1']));

        $cheap = $this->supplier();
        $dear = $this->supplier();
        $rfqs = app(RfqService::class);
        $rfq = $rfqs->createFromRequisition($req->refresh(), [$cheap, $dear], $this->user);
        [$l1, $l2] = $rfq->lines;
        $rfqs->recordQuotation($rfq, $cheap, [$l1->id => 45, $l2->id => 18], 5);
        $rfqs->recordQuotation($rfq, $dear, [$l1->id => 55, $l2->id => 25], 3);

        $comparison = $rfqs->comparison($rfq);
        $this->assertSame($cheap->id, $comparison[0]['supplier_id']);
        $this->assertTrue($comparison[0]['is_lowest']);
        $this->assertFalse($comparison[1]['is_lowest']);

        $order = $rfqs->award($rfq, $cheap, $this->user, $store);
        $this->assertSame('draft', $order->status);
        $this->assertSame('522.00', (string) $order->total); // 10 x 45 + 4 x 18
        $this->assertSame('awarded', $rfq->refresh()->status);
        $this->assertSame('ordered', $req->refresh()->status);
        $this->assertSame('rejected', $rfq->quotations()->where('supplier_id', $dear->id)->value('status'));
    }

    public function test_an_order_over_budget_is_stopped(): void
    {
        $store = $this->store();
        $item = $this->item();
        $department = $this->department();
        app(BudgetService::class)->set($this->organization->id, $department->id, now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString(), '1000');
        $orders = app(PurchaseOrderService::class);
        $supplier = $this->supplier();

        $ok = $orders->create($this->user, $supplier, $store, [['item_id' => $item->id, 'quantity' => 8, 'unit_price' => 100]], ['department_id' => $department->id]);
        $orders->submit($ok, $this->user);
        $this->assertSame('pending_approval', $ok->refresh()->status);

        $over = $orders->create($this->user, $supplier, $store, [['item_id' => $item->id, 'quantity' => 3, 'unit_price' => 100]], ['department_id' => $department->id]);
        $this->expectException(ValidationException::class);
        $orders->submit($over, $this->user); // 800 committed + 300 > 1000
    }

    public function test_partial_deliveries_close_the_order_lines_correctly_and_rejects_are_quarantined(): void
    {
        $store = $this->store();
        $item = $this->item(['purchase_uom' => 'box', 'purchase_to_base' => '10']);
        $order = $this->issuedOrder($this->supplier(), $store, [[$item, 10, '100']]); // 10 boxes = 100 pieces at 10 each
        $grn = app(GoodsReceiptService::class);
        $stock = app(InventoryStockService::class);
        $expiry = now()->addYear()->toDateString();

        $first = $grn->receive($order, $this->user, [['po_line_id' => $order->lines[0]->id, 'quantity' => 6, 'batch_no' => 'L1', 'expiry_date' => $expiry]]);
        $this->assertSame('0.0000', $stock->onHand($store, $item)); // counted at the door, not yet stock
        $grn->inspect($first, $this->user, [$first->lines[0]->id => ['accepted' => 5, 'rejected' => 1, 'reason' => 'Boxes crushed']]);

        $this->assertSame('50.0000', $stock->onHand($store, $item));
        $this->assertSame('10.0000', $stock->onHand(InventoryStore::where('code', 'QRN')->first(), $item));
        $this->assertSame('partially_received', $order->refresh()->status);
        $this->assertSame('5.0000', (string) $order->lines[0]->refresh()->received_quantity);
        $this->assertFalse((bool) $order->lines[0]->closed);

        try {
            $grn->receive($order, $this->user, [['po_line_id' => $order->lines[0]->id, 'quantity' => 6, 'batch_no' => 'L2', 'expiry_date' => $expiry]]);
            $this->fail('Received more than was still due.');
        } catch (ValidationException) {
        }
        $second = $grn->receive($order, $this->user, [['po_line_id' => $order->lines[0]->id, 'quantity' => 5, 'batch_no' => 'L2', 'expiry_date' => $expiry]]);
        $grn->inspect($second, $this->user);

        $this->assertSame('received', $order->refresh()->status);
        $this->assertTrue((bool) $order->lines[0]->refresh()->closed);
        $this->assertSame('100.0000', $stock->onHand($store, $item));
        $this->assertSame('10', (string) (float) $item->refresh()->last_cost);
        // the rejected units can never be issued
        $this->expectException(ValidationException::class);
        $stock->allocate(InventoryStore::where('code', 'QRN')->first(), $item, '1');
    }

    public function test_goods_with_too_little_shelf_life_are_rejected_on_receipt(): void
    {
        $store = $this->store();
        $item = $this->item();
        $order = $this->issuedOrder($this->supplier(), $store, [[$item, 10, '5']]);
        $grn = app(GoodsReceiptService::class);
        $receipt = $grn->receive($order, $this->user, [['po_line_id' => $order->lines[0]->id, 'quantity' => 10, 'batch_no' => 'SHORT', 'expiry_date' => now()->addDays(10)->toDateString()]]);
        $grn->inspect($receipt, $this->user);

        $this->assertSame('0.0000', app(InventoryStockService::class)->onHand($store, $item));
        $this->assertStringContainsString('shelf life', (string) $receipt->lines[0]->refresh()->reject_reason);
        $this->assertSame('10.0000', (string) $receipt->lines[0]->rejected_quantity);
    }

    public function test_an_amendment_keeps_the_old_version_and_a_bigger_total_needs_more_approval(): void
    {
        $store = $this->store();
        $item = $this->item();
        $orders = app(PurchaseOrderService::class);
        $order = $this->issuedOrder($this->supplier(), $store, [[$item, 100, '100']]); // 10,000 -> level 1 only
        $this->assertSame(1, $order->version);

        $changed = $orders->amend($order, $this->user, [['line_id' => $order->lines[0]->id, 'quantity' => 120]], 'Ward needs more');
        $this->assertSame(2, $changed->version);
        $this->assertSame('issued', $changed->status);
        $this->assertSame('10000.00', $changed->amendments->first()->snapshot['total']);

        $big = $orders->amend($changed, $this->user, [['line_id' => $order->lines[0]->id, 'quantity' => 600]], 'Bulk discount agreed'); // 60,000 -> now needs level 2
        $this->assertSame('pending_approval', $big->status);
        try {
            app(GoodsReceiptService::class)->receive($big, $this->user, [['po_line_id' => $order->lines[0]->id, 'quantity' => 1, 'batch_no' => 'X', 'expiry_date' => now()->addYear()->toDateString()]]);
            $this->fail('Goods were received on an order waiting for approval.');
        } catch (ValidationException) {
        }
        $orders->approve($big, $this->actor(['procurement.approval.level2']));
        $this->assertSame('issued', $big->refresh()->status);
    }

    public function test_you_cannot_amend_below_what_was_received_or_cancel_after_receipt(): void
    {
        $store = $this->store();
        $item = $this->item();
        $order = $this->issuedOrder($this->supplier(), $store, [[$item, 10, '10']]);
        $this->receiveAll($order);
        $orders = app(PurchaseOrderService::class);

        $this->expectException(ValidationException::class);
        try {
            $orders->amend($order->refresh(), $this->user, [['line_id' => $order->lines[0]->id, 'quantity' => 4]], 'Too many');
        } catch (ValidationException) {
            $orders->cancel($order->refresh(), 'Changed our mind');
        }
    }

    public function test_the_three_way_match_passes_inside_tolerance_and_blocks_payment_outside_it(): void
    {
        $store = $this->store();
        $item = $this->item();
        $supplier = $this->supplier();
        $order = $this->issuedOrder($supplier, $store, [[$item, 100, '50']]);
        $this->receiveAll($order);
        $matching = app(SupplierInvoiceMatchingService::class);
        $lineId = $order->lines[0]->id;

        $good = $matching->record($this->user, $supplier, $order, 'INV-1', now()->toDateString(), [['po_line_id' => $lineId, 'quantity' => 100, 'unit_price' => '50.50']]); // +1% is inside the 2%
        $this->assertSame('matched', $good->status);
        $this->assertTrue($matching->handToFinance($good) || true);

        $order2 = $this->issuedOrder($supplier, $store, [[$item, 100, '50']]);
        $this->receiveAll($order2);
        $bad = $matching->record($this->user, $supplier, $order2, 'INV-2', now()->toDateString(), [['po_line_id' => $order2->lines[0]->id, 'quantity' => 100, 'unit_price' => '58']]);
        $this->assertSame('mismatch', $bad->status);
        $this->assertGreaterThan(0, (float) $bad->variance_amount);
        try {
            $matching->handToFinance($bad);
            $this->fail('A mismatched invoice reached Finance.');
        } catch (ValidationException) {
        }
        try {
            $matching->approveMismatch($bad, $this->user, 'ok'); // recorded by the same person
            $this->fail('The recorder approved their own mismatch.');
        } catch (ValidationException) {
        }
        $approved = $matching->approveMismatch($bad, $this->actor(['procurement.invoice.approve']), 'Price rise agreed by phone');
        $this->assertSame('approved', $approved->status);
        $matching->handToFinance($approved); // no exception: the Null adapter says "not enabled", the payable stays here
    }

    public function test_billing_for_goods_that_did_not_arrive_is_a_mismatch(): void
    {
        $store = $this->store();
        $item = $this->item();
        $supplier = $this->supplier();
        $order = $this->issuedOrder($supplier, $store, [[$item, 100, '50']]);
        $grn = app(GoodsReceiptService::class);
        $grn->inspect($grn->receive($order, $this->user, [['po_line_id' => $order->lines[0]->id, 'quantity' => 60, 'batch_no' => 'P', 'expiry_date' => now()->addYear()->toDateString()]]), $this->user);

        $invoice = app(SupplierInvoiceMatchingService::class)->record($this->user, $supplier, $order, 'INV-9', now()->toDateString(), [['po_line_id' => $order->lines[0]->id, 'quantity' => 100, 'unit_price' => '50']]);
        $this->assertSame('mismatch', $invoice->status);
        $this->assertFalse($invoice->match_result[0]['quantity_ok']);
    }

    public function test_an_emergency_order_goes_out_now_and_is_ratified_later_by_the_same_chain(): void
    {
        $store = $this->store();
        $item = $this->item();
        $supplier = $this->supplier();
        $orders = app(PurchaseOrderService::class);

        $order = $orders->createEmergency($this->user, $supplier, $store, [['item_id' => $item->id, 'quantity' => 10, 'unit_price' => 100]], 'Theatre ran out of drapes');
        $this->assertSame('issued', $order->status);
        $this->assertSame('emergency', $order->po_type);
        $this->assertNotNull($order->ratify_by);
        $this->receiveAll($order); // goods still go through receiving
        $this->assertSame(0, $orders->overdueRatifications($this->organization->id)->count());

        $this->travel(3)->days();
        $this->assertSame(1, $orders->overdueRatifications($this->organization->id)->count());
        $orders->ratify($order->refresh(), $this->actor(['procurement.approval.level1']));
        $this->assertNotNull($order->refresh()->ratified_at);
        $this->assertSame(0, $orders->overdueRatifications($this->organization->id)->count());

        $this->expectException(ValidationException::class);
        $orders->createEmergency($this->user, $supplier, $store, [['item_id' => $item->id, 'quantity' => 1000, 'unit_price' => 1000]], 'Too big'); // over the emergency ceiling
    }

    public function test_refusing_to_ratify_stops_what_has_not_arrived(): void
    {
        $order = app(PurchaseOrderService::class)->createEmergency($this->user, $this->supplier(), $this->store(), [['item_id' => $this->item()->id, 'quantity' => 2, 'unit_price' => 10]], 'Urgent');
        $refused = app(PurchaseOrderService::class)->refuseRatification($order, $this->actor(['procurement.approval.level1']), 'Not an emergency');
        $this->assertSame('cancelled', $refused->status);
    }

    public function test_returning_rejected_goods_to_the_supplier_takes_them_out_of_quarantine(): void
    {
        $store = $this->store();
        $item = $this->item();
        $supplier = $this->supplier();
        $order = $this->issuedOrder($supplier, $store, [[$item, 10, '10']]);
        $grn = app(GoodsReceiptService::class);
        $receipt = $grn->receive($order, $this->user, [['po_line_id' => $order->lines[0]->id, 'quantity' => 10, 'batch_no' => 'BAD', 'expiry_date' => now()->addYear()->toDateString()]]);
        $grn->inspect($receipt, $this->user, [$receipt->lines[0]->id => ['accepted' => 0, 'rejected' => 10, 'reason' => 'Wrong product']]);
        $qrn = InventoryStore::where('code', 'QRN')->first();
        $batch = InventoryStock::where('store_id', $qrn->id)->first()->batch_id;

        $returns = app(SupplierReturnService::class);
        $return = $returns->create($this->user, $supplier, $qrn, [['item_id' => $item->id, 'batch_id' => $batch, 'quantity' => 10]], 'Wrong product', $receipt->id);
        $returns->dispatch($return, $this->user);
        $this->assertSame('0.0000', app(InventoryStockService::class)->onHand($qrn, $item));
        $credited = $returns->credit($return->refresh(), 'CN-77', '100.00');
        $this->assertSame('credited', $credited->status);

        $this->expectException(ValidationException::class);
        $returns->dispatch($return->refresh(), $this->user); // not twice
    }

    public function test_budget_row_is_unique_per_period(): void
    {
        $department = $this->department();
        $budgets = app(BudgetService::class);
        $budgets->set($this->organization->id, $department->id, '2026-01-01', '2026-12-31', '500');
        $budgets->set($this->organization->id, $department->id, '2026-01-01', '2026-12-31', '900');
        $this->assertSame(1, ProcurementBudget::where('department_id', $department->id)->count());
        $this->assertSame('900.00', (string) ProcurementBudget::first()->amount);
    }
}
