<?php

namespace Tests\Feature\Inventory;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingItem;
use App\Models\Billing\ChargeEventLog;
use App\Models\Inventory\InventoryAdjustment;
use App\Models\Inventory\InventoryConsumption;
use App\Models\Inventory\InventoryStore;
use App\Models\Patient;
use App\Services\Inventory\InventoryAdjustmentService;
use App\Services\Inventory\InventoryConsumptionService;
use App\Services\Inventory\InventoryCountService;
use App\Services\Inventory\InventoryExpiryService;
use App\Services\Inventory\InventoryIssueService;
use App\Services\Inventory\InventoryLedgerReconciler;
use App\Services\Inventory\InventoryReportService;
use App\Services\Inventory\InventoryStockService;
use App\Services\Inventory\InventoryTransferService;
use App\Services\Inventory\ReorderService;
use Illuminate\Validation\ValidationException;

class InventoryOperationsTest extends InventoryTestCase
{
    public function test_a_transfer_moves_stock_in_two_ledger_rows_with_an_in_transit_state(): void
    {
        $from = $this->store();
        $to = $this->store('ward');
        $item = $this->item();
        $this->stock($from, $item, '100', 'B1', now()->addMonths(3)->toDateString());
        $this->stock($from, $item, '100', 'B2', now()->addMonths(9)->toDateString());
        $transfers = app(InventoryTransferService::class);
        $stock = app(InventoryStockService::class);

        $transfer = $transfers->request($this->user, $from, $to, [['item_id' => $item->id, 'quantity' => 130]]);
        $this->assertSame('200.0000', $stock->onHand($from, $item));
        $transfers->dispatch($transfer, $this->user);

        $this->assertSame('dispatched', $transfer->refresh()->status);
        $this->assertSame('70.0000', $stock->onHand($from, $item));
        $this->assertSame('0.0000', $stock->onHand($to, $item)); // in transit: neither store has it
        $this->assertSame(2, $transfer->lines()->count()); // 100 from the earlier batch, 30 from the later

        $transfers->receive($transfer->refresh(), $this->actor(['inventory.transfer.receive']));
        $this->assertSame('130.0000', $stock->onHand($to, $item));
        $this->assertSame(0, app(InventoryLedgerReconciler::class)->drift($this->organization->id)->count());
    }

    public function test_what_the_receiver_did_not_get_goes_back_to_the_sender(): void
    {
        $from = $this->store();
        $to = $this->store('ward');
        $item = $this->item(['tracks_batch' => false, 'tracks_expiry' => false]);
        app(InventoryStockService::class)->receive($from, $item, 0, '50', 'opening_balance', 'O');
        $transfers = app(InventoryTransferService::class);
        $transfer = $transfers->dispatch($transfers->request($this->user, $from, $to, [['item_id' => $item->id, 'quantity' => 20]]), $this->user);
        $transfers->receive($transfer, $this->user, [$transfer->lines[0]->id => 15]);

        $stock = app(InventoryStockService::class);
        $this->assertSame('15.0000', $stock->onHand($to, $item));
        $this->assertSame('35.0000', $stock->onHand($from, $item)); // 30 + the 5 that never arrived
    }

    public function test_a_transfer_cannot_overdraw_the_sender_or_target_itself(): void
    {
        $from = $this->store();
        $item = $this->item(['tracks_batch' => false, 'tracks_expiry' => false]);
        app(InventoryStockService::class)->receive($from, $item, 0, '5', 'opening_balance', 'O');
        $transfers = app(InventoryTransferService::class);

        try {
            $transfers->request($this->user, $from, $from, [['item_id' => $item->id, 'quantity' => 1]]);
            $this->fail('A store sent stock to itself.');
        } catch (ValidationException) {
        }
        $transfer = $transfers->request($this->user, $from, $this->store('ward'), [['item_id' => $item->id, 'quantity' => 9]]);
        $this->expectException(ValidationException::class);
        $transfers->dispatch($transfer, $this->user);
    }

    public function test_a_department_request_is_issued_oldest_expiry_first_and_unused_stock_comes_back(): void
    {
        $store = $this->store();
        $dept = $this->department();
        $item = $this->item();
        $this->stock($store, $item, '10', 'B1', now()->addMonths(2)->toDateString());
        $this->stock($store, $item, '10', 'B2', now()->addMonths(8)->toDateString());
        $issues = app(InventoryIssueService::class);

        $request = $issues->request($this->user, $dept, $store, [['item_id' => $item->id, 'quantity' => 25]]);
        $issue = $issues->issueRequest($request, $this->user); // only 20 on the shelf
        $this->assertSame('partially_issued', $request->refresh()->status);
        $this->assertSame(20.0, (float) $issue->lines->sum('quantity'));
        $this->assertSame('5.0000', bcsub('25', (string) $request->lines[0]->issued_quantity, 4));

        $issues->returnLine($issue->lines[0], $this->user, 4, 'Not needed');
        $this->assertSame('4.0000', app(InventoryStockService::class)->onHand($store, $item));
        $this->expectException(ValidationException::class);
        $issues->returnLine($issue->lines[0]->refresh(), $this->user, 7, 'More than was issued');
    }

    public function test_a_variance_posts_only_when_a_different_person_approves_the_count(): void
    {
        $store = $this->store();
        $item = $this->item();
        $this->stock($store, $item, '100', 'B1');
        $counter = $this->actor(['inventory.count.perform']);
        $approver = $this->actor(['inventory.count.approve']);
        $counts = app(InventoryCountService::class);

        $count = $counts->start($counter, $store);
        $line = $count->lines[0];
        $counts->enter($line, 97, 'Three cartons damaged'); // 3% — no note needed, but we gave one
        $counts->submit($count, $counter);
        $this->assertSame('100.0000', app(InventoryStockService::class)->onHand($store, $item)); // nothing posted yet

        try {
            $counts->approve($count->refresh(), $counter);
            $this->fail('The counter approved their own count.');
        } catch (ValidationException) {
        }
        $counts->approve($count->refresh(), $approver);
        $this->assertSame('97.0000', app(InventoryStockService::class)->onHand($store, $item));
        $this->assertSame('approved', InventoryAdjustment::first()->status);
        $this->assertSame(0, app(InventoryLedgerReconciler::class)->drift($this->organization->id)->count());
    }

    public function test_a_large_difference_needs_a_reason_and_every_line_must_be_counted(): void
    {
        $store = $this->store();
        $item = $this->item();
        $this->stock($store, $item, '100', 'B1');
        $counts = app(InventoryCountService::class);
        $count = $counts->start($this->user, $store);

        try {
            $counts->submit($count, $this->user);
            $this->fail('Submitted an uncounted sheet.');
        } catch (ValidationException) {
        }
        $counts->enter($count->lines[0], 80);
        $this->expectException(ValidationException::class);
        $counts->submit($count->refresh(), $this->user); // 20% with no reason
    }

    public function test_adjustments_need_a_second_person_and_cannot_overdraw(): void
    {
        $store = $this->store();
        $item = $this->item();
        $batch = $this->stock($store, $item, '10', 'B1');
        $adjustments = app(InventoryAdjustmentService::class);
        $adjustment = $adjustments->request($this->user, $store, $item, $batch, '-4', 'damage', 'Dropped pallet');

        try {
            $adjustments->approve($adjustment, $this->user);
            $this->fail('Self-approved an adjustment.');
        } catch (ValidationException) {
        }
        $adjustments->approve($adjustment, $this->actor(['inventory.adjustment.approve']));
        $this->assertSame('6.0000', app(InventoryStockService::class)->onHand($store, $item));

        $tooMuch = $adjustments->request($this->user, $store, $item, $batch, '-50', 'theft', 'Missing');
        $this->expectException(ValidationException::class);
        $adjustments->approve($tooMuch, $this->actor(['inventory.adjustment.approve']));
    }

    public function test_the_expiry_sweep_blocks_expired_batches_and_proposes_a_write_off_without_duplicates(): void
    {
        $store = $this->store();
        $item = $this->item();
        $old = $this->stock($store, $item, '10', 'OLD', now()->subDays(2)->toDateString());
        $this->stock($store, $item, '10', 'FRESH', now()->addMonths(6)->toDateString());
        $expiry = app(InventoryExpiryService::class);

        $first = $expiry->sweep($this->organization->id, $this->user);
        $second = $expiry->sweep($this->organization->id, $this->user);

        $this->assertSame(1, $first['expired']);
        $this->assertSame(1, $first['writeoffs_proposed']);
        $this->assertSame(0, $second['writeoffs_proposed']);
        $this->assertSame('expired', $this->batchOf($old)->status);
        $this->assertSame(1, InventoryAdjustment::where('reason_code', 'expired')->count());

        $adj = InventoryAdjustment::first();
        app(InventoryAdjustmentService::class)->approve($adj, $this->actor(['inventory.adjustment.approve']));
        $this->assertSame('10.0000', app(InventoryStockService::class)->onHand($store, $item)); // only the fresh batch is left
        $this->assertSame(1, $expiry->risk($this->organization->id, 365)->where('days_left', '>', 0)->count());
    }

    public function test_a_recall_moves_every_batch_to_quarantine_and_traces_where_it_went(): void
    {
        $store = $this->store();
        $ward = $this->store('ward');
        $dept = $this->department();
        $item = $this->item();
        $batch = $this->stock($store, $item, '50', 'RC1');
        $this->stock($store, $item, '20', 'SAFE');
        $transfers = app(InventoryTransferService::class);
        $transfers->receive($transfers->dispatch($transfers->request($this->user, $store, $ward, [['item_id' => $item->id, 'quantity' => 10, 'batch_id' => $batch]]), $this->user), $this->user);
        app(InventoryIssueService::class)->issueDirect($this->user, $store, $dept, [['item_id' => $item->id, 'quantity' => 5]]);

        $recall = app(InventoryExpiryService::class)->recall($item, 'RC1', 'Sterility failure', $this->user);

        $this->assertSame('45.0000', (string) $recall->quantity_blocked); // 35 left in the store (50 - 10 sent to the ward - 5 issued) + the 10 in the ward
        $this->assertSame('recalled', $this->batchOf($batch)->status);
        $stock = app(InventoryStockService::class);
        $this->assertSame('20.0000', $stock->onHand($store, $item)); // SAFE only
        $this->assertSame('0.0000', $stock->onHand($ward, $item));
        $this->assertSame('45.0000', $stock->onHand(InventoryStore::where('code', 'QRN')->first(), $item));
        $trace = app(InventoryExpiryService::class)->trace($this->batchOf($batch));
        $this->assertNotEmpty($trace['issued']);
        $this->expectException(ValidationException::class);
        $stock->allocate($ward, $item, '1');
    }

    public function test_a_batch_on_hold_returns_to_the_store_it_came_from(): void
    {
        $store = $this->store();
        $item = $this->item();
        $batch = $this->stock($store, $item, '30', 'HOLD');
        $expiry = app(InventoryExpiryService::class);

        $expiry->quarantine($this->batchOf($batch), $this->user, 'Cold chain doubt');
        $this->assertSame('0.0000', app(InventoryStockService::class)->onHand($store, $item));
        $expiry->release($this->batchOf($batch)->refresh(), $this->user, 'Tested fine');
        $this->assertSame('30.0000', app(InventoryStockService::class)->onHand($store, $item));
        $this->assertSame('active', $this->batchOf($batch)->status);
        $this->assertSame(0, app(InventoryLedgerReconciler::class)->drift($this->organization->id)->count());
    }

    public function test_patient_use_publishes_one_charge_event_and_repeats_charge_nothing_twice(): void
    {
        $store = $this->store('ot');
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $item = $this->item(['charge_service_code' => 'INV-SUTURE', 'tracks_batch' => false, 'tracks_expiry' => false]);
        app(InventoryStockService::class)->receive($store, $item, 0, '20', 'opening_balance', 'O');
        $category = BillingCategory::create(['organization_id' => $this->organization->id, 'branch_id' => null, 'code' => 'CONS', 'name' => 'Consumables', 'is_active' => true]);
        BillingItem::create(['organization_id' => $this->organization->id, 'branch_id' => null, 'category_id' => $category->id, 'item_code' => 'INV-SUTURE', 'item_type' => 'service', 'name' => 'Suture', 'base_price' => '250.00', 'is_taxable' => false, 'is_active' => true]);

        $rows = app(InventoryConsumptionService::class)->record($this->user, $store, $item, 2, ['patient_id' => $patient->id, 'encounter_id' => null, 'admission_id' => 77, 'source_module' => 'ot']);

        $this->assertSame('18.0000', app(InventoryStockService::class)->onHand($store, $item));
        $this->assertCount(1, $rows);
        $this->assertSame(1, ChargeEventLog::where('idempotency_key', $rows[0]->refresh()->charge_key)->count());
        $this->assertSame('500', (string) (float) BillingCharge::query()->where('patient_id', $patient->id)->sum('net_amount'));
    }

    public function test_a_charged_item_must_be_used_in_whole_units(): void
    {
        $store = $this->store('ot');
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $item = $this->item(['charge_service_code' => 'INV-GEL', 'tracks_batch' => false, 'tracks_expiry' => false]);
        app(InventoryStockService::class)->receive($store, $item, 0, '20', 'opening_balance', 'O');

        $this->expectException(ValidationException::class);
        app(InventoryConsumptionService::class)->record($this->user, $store, $item, 1.5, ['patient_id' => $patient->id, 'admission_id' => 3]);
    }

    public function test_use_without_a_patient_or_without_a_charge_code_charges_nothing(): void
    {
        $store = $this->store('ward');
        $item = $this->item(['tracks_batch' => false, 'tracks_expiry' => false]);
        app(InventoryStockService::class)->receive($store, $item, 0, '20', 'opening_balance', 'O');
        $rows = app(InventoryConsumptionService::class)->record($this->user, $store, $item, 3, ['department_id' => $this->department()->id]);

        $this->assertNull($rows[0]->charge_key);
        $this->assertSame(0, ChargeEventLog::count());
        $this->assertSame(1, InventoryConsumption::count());
    }

    public function test_reorder_suggestions_come_from_real_use_and_ignore_what_is_already_on_order(): void
    {
        $store = $this->store();
        $dept = $this->department();
        $busy = $this->item(['code' => 'BUSY', 'reorder_min' => 50, 'reorder_max' => 400, 'tracks_batch' => false, 'tracks_expiry' => false, 'purchase_uom' => 'box', 'purchase_to_base' => '10']);
        $quiet = $this->item(['code' => 'QUIET', 'reorder_min' => 5, 'reorder_max' => 20, 'tracks_batch' => false, 'tracks_expiry' => false]);
        $stock = app(InventoryStockService::class);
        $stock->receive($store, $busy, 0, '120', 'opening_balance', 'B');
        $stock->receive($store, $quiet, 0, '100', 'opening_balance', 'Q');
        app(InventoryIssueService::class)->issueDirect($this->user, $store, $dept, [['item_id' => $busy->id, 'quantity' => 90]]); // 30 left, 90 used in the window

        $rows = collect(app(ReorderService::class)->suggestions($this->organization->id));
        $this->assertSame(['BUSY'], $rows->pluck('code')->all());
        $this->assertSame('37', $rows->first()['suggested_purchase_units']); // (400 - 30) / 10 boxes

        $supplier = $this->supplier();
        $order = $this->issuedOrder($supplier, $store, [[$busy, 37, '100']]);
        $this->assertSame($order->status, 'issued');
        $this->assertSame([], app(ReorderService::class)->suggestions($this->organization->id)); // on order now
    }

    public function test_the_dashboard_and_valuation_read_the_same_stock_the_ledger_holds(): void
    {
        $store = $this->store();
        $item = $this->item();
        $this->stock($store, $item, '10', 'V1', null, '25');
        $reports = app(InventoryReportService::class);

        $this->assertSame('250.00', $reports->dashboard($this->organization->id)['stock_value']);
        $this->assertSame('250', (string) (float) $reports->valuation($this->organization->id)->sum('value'));
    }

    public function test_consumption_report_adds_issues_and_point_of_care_use_per_department(): void
    {
        $store = $this->store();
        $dept = $this->department();
        $item = $this->item(['tracks_batch' => false, 'tracks_expiry' => false, 'standard_cost' => '4']);
        $stock = app(InventoryStockService::class);
        $stock->receive($store, $item, 0, '100', 'opening_balance', 'C');
        app(InventoryIssueService::class)->issueDirect($this->user, $store, $dept, [['item_id' => $item->id, 'quantity' => 10]]);
        app(InventoryConsumptionService::class)->record($this->user, $store, $item, 5, ['department_id' => $dept->id]);

        $rows = app(InventoryReportService::class)->consumptionByDepartment($this->organization->id, now()->subDay(), now()->addDay());
        $this->assertSame(1, $rows->count());
        $this->assertSame('15', (string) (float) $rows->first()->quantity);
        $this->assertSame('60', (string) (float) $rows->first()->cost);
    }
}
