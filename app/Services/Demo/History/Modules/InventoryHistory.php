<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Department;
use App\Models\Inventory\InventoryAdjustment;
use App\Models\Inventory\InventoryBatch;
use App\Models\Inventory\InventoryIssue;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\ProcurementContract;
use App\Models\Inventory\ProcurementPurchaseOrder;
use App\Models\Inventory\ProcurementSupplier;
use App\Models\Inventory\ProcurementSupplierReturn;
use App\Models\User;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Inventory\BudgetService;
use App\Services\Inventory\GoodsReceiptService;
use App\Services\Inventory\InventoryAdjustmentService;
use App\Services\Inventory\InventoryConsumptionService;
use App\Services\Inventory\InventoryCountService;
use App\Services\Inventory\InventoryExpiryService;
use App\Services\Inventory\InventoryIssueService;
use App\Services\Inventory\InventoryMasterService;
use App\Services\Inventory\InventoryStockService;
use App\Services\Inventory\InventoryTransferService;
use App\Services\Inventory\ProcurementPricing;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\ReorderService;
use App\Services\Inventory\RequisitionService;
use App\Services\Inventory\RfqService;
use App\Services\Inventory\SupplierInvoiceMatchingService;
use App\Services\Inventory\SupplierReturnService;
use App\Services\Inventory\SupplierService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Stores and purchasing, six months. Day one has empty stores: suppliers are qualified, the first requisitions go through approval and
 * purchase orders, deliveries arrive (partly, sometimes with rejects), and from then on the weekly reorder rhythm keeps the central store
 * stocked while wards and theatre draw from their own stores. Along the way: department requests and issues, transfers, patient-linked use
 * (charged through Billing), invoices that match and some that do not, goods sent back, monthly counts, expiry write-offs, one hold, one
 * recall and emergency orders (one approved afterwards, one left overdue). Everything goes through the module's own services.
 */
class InventoryHistory implements DemoHistoryModule
{
    /** Items charged to patients when used for them: code => [name, price]. Prices are invented training prices (the Billing catalogue creates them). */
    public const CHARGED = [
        'INV-CANNULA' => ['IV cannula', 180], 'INV-DRESSING' => ['Dressing pack', 250], 'INV-CATHETER' => ['Urinary catheter', 420], 'INV-SUTURE' => ['Suture pack', 350],
    ];

    /** code, name, category, base unit, pack unit, units per pack, batch+expiry, cost per base unit, reorder at, refill to, shelf months, billing code, supplier key */
    private const ITEMS = [
        ['GLOVE-M', 'Examination gloves, medium', 'SURG', 'pair', 'box', 100, true, 9, 600, 2400, 30, null, 'A'],
        ['GLOVE-S', 'Examination gloves, small', 'SURG', 'pair', 'box', 100, true, 9, 300, 1200, 30, null, 'A'],
        ['SYRINGE-5', 'Syringe 5 ml', 'SURG', 'each', 'box', 100, true, 6, 400, 1600, 36, null, 'A'],
        ['SYRINGE-10', 'Syringe 10 ml', 'SURG', 'each', 'box', 100, true, 8, 300, 1200, 36, null, 'A'],
        ['CANNULA-20G', 'IV cannula 20G', 'SURG', 'each', 'box', 50, true, 45, 150, 600, 36, 'INV-CANNULA', 'G'],
        ['CANNULA-22G', 'IV cannula 22G', 'SURG', 'each', 'box', 50, true, 45, 100, 400, 36, 'INV-CANNULA', 'G'],
        ['SUTURE-VIC', 'Absorbable suture pack', 'SURG', 'each', 'box', 12, true, 140, 60, 240, 36, 'INV-SUTURE', 'G'],
        ['SUTURE-SILK', 'Silk suture pack', 'SURG', 'each', 'box', 12, true, 90, 48, 180, 36, 'INV-SUTURE', 'G'],
        ['IV-SET', 'IV giving set', 'SURG', 'each', 'box', 50, true, 30, 200, 800, 30, null, 'A'],
        ['CATHETER-FOLEY', 'Foley catheter', 'SURG', 'each', 'box', 10, true, 110, 40, 160, 36, 'INV-CATHETER', 'G'],
        ['MASK-SURG', 'Surgical mask', 'SURG', 'each', 'box', 50, true, 3, 500, 2000, 36, null, 'A'],
        ['MASK-N95', 'N95 respirator', 'SURG', 'each', 'box', 20, true, 55, 60, 240, 36, null, 'A'],
        ['GAUZE', 'Sterile gauze pack', 'DRESS', 'pack', 'carton', 50, true, 12, 300, 1200, 4, null, 'B'],
        ['DRESSING-PACK', 'Dressing pack', 'DRESS', 'each', 'box', 20, true, 70, 100, 400, 18, 'INV-DRESSING', 'B'],
        ['SPIRIT-SWAB', 'Alcohol swab', 'DRESS', 'each', 'box', 200, true, 1, 1000, 4000, 3, null, 'B'],
        ['BANDAGE-CREPE', 'Crepe bandage', 'DRESS', 'roll', 'box', 12, true, 40, 80, 300, 30, null, 'B'],
        ['BEDSHEET', 'Bed sheet', 'LINEN', 'each', 'bale', 20, false, 220, 60, 240, null, null, 'C'],
        ['PILLOWCASE', 'Pillow case', 'LINEN', 'each', 'bale', 20, false, 60, 60, 240, null, null, 'C'],
        ['GOWN-PATIENT', 'Patient gown', 'LINEN', 'each', 'bale', 10, false, 180, 40, 160, null, null, 'C'],
        ['TOWEL', 'Hand towel', 'LINEN', 'each', 'bale', 20, false, 45, 60, 240, null, null, 'C'],
        ['PAPER-A4', 'Printer paper A4', 'STAT', 'ream', 'carton', 5, false, 520, 20, 80, null, null, 'D'],
        ['TONER', 'Printer toner', 'STAT', 'each', 'box', 1, false, 6500, 3, 10, null, null, 'D'],
        ['WRISTBAND', 'Patient wristband', 'STAT', 'each', 'box', 200, false, 8, 400, 1600, null, null, 'D'],
        ['DISINFECT-5L', 'Surface disinfectant 5 l', 'HK', 'can', 'carton', 4, false, 950, 12, 48, null, null, 'E'],
        ['BIN-LINER', 'Bin liner roll', 'HK', 'roll', 'carton', 20, false, 75, 40, 160, null, null, 'E'],
        ['HAND-SOAP', 'Liquid hand soap 1 l', 'HK', 'each', 'carton', 12, false, 160, 24, 96, null, null, 'E'],
        ['MOP-HEAD', 'Mop head', 'HK', 'each', 'box', 10, false, 190, 15, 60, null, null, 'E'],
        ['SPARE-FILTER', 'Air filter (spare)', 'SPARE', 'each', 'box', 1, false, 2800, 4, 12, null, null, 'F'],
        ['SPARE-BULB', 'Theatre lamp bulb (spare)', 'SPARE', 'each', 'box', 1, false, 3800, 3, 10, null, null, 'F'],
    ];

    private const CATEGORIES = ['SURG' => ['Surgical and clinical consumables', 'surgical'], 'DRESS' => ['Dressings', 'consumable'], 'LINEN' => ['Linen', 'linen'], 'STAT' => ['Stationery', 'stationery'], 'HK' => ['Housekeeping', 'housekeeping'], 'SPARE' => ['Equipment spares', 'spare']];

    private const SUPPLIERS = [
        'A' => ['MEDLINE', 'Medline Supplies Ltd'], 'B' => ['WOUNDCARE', 'Woundcare Bangladesh'], 'C' => ['TEXLINEN', 'Tex Linen Mills'], 'D' => ['OFFICEPRO', 'Office Pro Stationers'],
        'E' => ['CLEANCO', 'CleanCo Hygiene'], 'F' => ['BIOSPARE', 'BioSpare Engineering'], 'G' => ['SURGICORE', 'SurgiCore Medical'], 'H' => ['QUICKMED', 'QuickMed Traders'], 'X' => ['OLDTRADE', 'Old Trade Corner (closed)'],
    ];

    /** @var array<string, int> */
    private array $summary = [];

    /** Work scheduled for a later day, keyed by date (arrow functions copy local arrays, so these live on the object). @var array<string, array<int, int>> */
    private array $deliveries = [];

    /** @var array<string, list<int>> */
    private array $invoicesDue = [];

    /** @var array<string, list<int>> */
    private array $creditsDue = [];

    /** @var array<string, int> */
    private array $releasesDue = [];

    public function __construct(
        private readonly InventoryMasterService $masters,
        private readonly SupplierService $suppliers,
        private readonly RequisitionService $requisitions,
        private readonly RfqService $rfqs,
        private readonly PurchaseOrderService $orders,
        private readonly GoodsReceiptService $receipts,
        private readonly SupplierInvoiceMatchingService $matching,
        private readonly SupplierReturnService $returns,
        private readonly InventoryTransferService $transfers,
        private readonly InventoryIssueService $issues,
        private readonly InventoryConsumptionService $consumption,
        private readonly InventoryCountService $counts,
        private readonly InventoryAdjustmentService $adjustments,
        private readonly InventoryExpiryService $expiry,
        private readonly ReorderService $reorder,
        private readonly BudgetService $budgets,
    ) {}

    public function name(): string
    {
        return 'Stores and purchasing';
    }

    public function module(): string
    {
        return 'inventory';
    }

    public function run(HistoryContext $context): string
    {
        $org = $context->organization->id;
        $this->summary = array_fill_keys(['pos', 'received', 'rejected_lines', 'invoices', 'mismatch', 'returns', 'issues', 'transfers', 'uses', 'stockouts', 'counts', 'writeoffs', 'emergency', 'skipped', 'rfqs'], 0);
        $storekeeper = $context->staff('storekeeper')->first();
        $manager = $context->staff('inventory_manager')->first();
        $buyer = $context->staff('procurement_officer')->first();
        $buyingManager = $context->staff('procurement_manager')->first();

        // --- masters, on the day before the history starts
        $setupAt = $context->from->subDay()->setTime(9, 0);
        [$stores, $items, $suppliers, $itemSupplier, $departments] = $context->at($setupAt, fn () => $this->setup($context, $buyer, $manager, $buyingManager));
        $central = $stores['CENTRAL'];
        $recipientIds = array_filter([$manager->id, $buyer->id, $buyingManager->id]);

        $this->deliveries = $this->invoicesDue = $this->creditsDue = $this->releasesDue = [];
        $emergencyDone = 0;
        $admissions = collect($context->shared('ipd_admissions'));
        $dayNo = 0;
        $days = collect($context->days());

        foreach ($days as $day) {
            $dayNo++;
            $key = $day->toDateString();

            // 07:00 expiry sweep; yesterday's write-off requests are approved by the manager
            $context->at($day->setTime(7, 0), function () use ($org, $storekeeper, $manager, $recipientIds) {
                $this->expiry->sweep($org, $storekeeper, $recipientIds);
                InventoryAdjustment::query()->where(['organization_id' => $org, 'status' => 'requested'])->where('created_at', '<', now()->subHours(12))->orderBy('id')->each(function (InventoryAdjustment $a) use ($manager) {
                    try {
                        $this->adjustments->approve($a, $manager);
                        $this->summary['writeoffs']++;
                    } catch (ValidationException) {
                        $this->summary['skipped']++;
                    }
                });
            });

            // 09:00 buying: the first day buys the opening stock; after that every Monday looks at what is low
            if ($dayNo === 1 || $day->isMonday()) {
                $context->at($day->setTime(9, 0), fn () => $this->replenish($context, $central, $items, $itemSupplier, $suppliers, $storekeeper, $manager, $buyer, $buyingManager, $day, $dayNo === 1));
            }

            // 10:00 deliveries due today (a partial first delivery leaves the rest for a few days later)
            $due = $this->deliveries[$key] ?? [];
            unset($this->deliveries[$key]);
            foreach ($due as $poId => $attempt) {
                $context->at($day->setTime(10, 0), fn () => $this->deliver($context, $poId, $attempt, $storekeeper, $day));
            }

            // 11:00 supplier invoices that have come in
            foreach ($this->invoicesDue[$key] ?? [] as $poId) {
                $context->at($day->setTime(11, 0), fn () => $this->invoice($context, $poId, $buyer, $buyingManager));
            }

            // 12:00 department requests (Tuesday and Friday) and the weekly top-up transfers to the ward and theatre stores (Wednesday)
            if (($day->isTuesday() || $day->isFriday()) && $dayNo > 3) {
                $context->at($day->setTime(12, 0), fn () => $this->departmentRequests($context, $central, $items, $departments, $storekeeper, $manager));
            }
            if ($day->isWednesday() && $dayNo > 3) {
                $context->at($day->setTime(12, 30), fn () => $this->topUp($context, $central, $stores, $items, $storekeeper));
            }

            // 14:00 use at the point of care: new admissions on the ward (cannulas, dressings, catheters) and the odd operation (sutures)
            $newToday = $admissions->filter(fn ($a) => CarbonImmutable::parse($a['at'])->toDateString() === $key);
            foreach ($newToday as $a) {
                $context->at($day->setTime(14, 0), fn () => $this->useForPatient($context, $a, $stores, $items, $storekeeper));
            }
            if ($dayNo > 5) {
                $context->at($day->setTime(15, 0), fn () => $this->housekeepingUse($context, $stores, $items, $departments, $storekeeper));
            }

            // Friday: rejected goods go back to their supplier; a credit note follows
            if ($day->isFriday()) {
                $context->at($day->setTime(15, 30), fn () => $this->sendBack($context, $buyer, $day));
            }
            foreach ($this->creditsDue[$key] ?? [] as $returnId) {
                $context->at($day->setTime(11, 30), function () use ($returnId) {
                    $return = ProcurementSupplierReturn::find($returnId);
                    $return && $this->returns->credit($return, 'CN-'.$return->number, number_format($return->lines->sum(fn ($l) => (float) $l->quantity * (float) $l->unit_cost), 2, '.', ''));
                });
            }

            // month end: a count of part of the central store, and the supplier scorecards
            if ($day->day === 25 && $dayNo > 20) {
                $context->at($day->setTime(16, 0), fn () => $this->count($context, $central, $storekeeper, $manager));
                $context->at($day->setTime(16, 30), fn () => $this->score($suppliers, $buyingManager, $recipientIds, $org));
            }

            // events worth having once in the six months
            if ($dayNo === (int) round($days->count() * 0.35)) {
                $context->at($day->setTime(13, 0), fn () => $this->hold($central, $manager, $day));
            }
            if (isset($this->releasesDue[$key])) {
                $context->at($day->setTime(9, 30), fn () => $this->release($this->releasesDue[$key], $manager));
            }
            if ($dayNo === (int) round($days->count() * 0.5)) {
                $context->at($day->setTime(13, 30), fn () => $this->recall($items, $manager));
            }
            if (in_array($dayNo, [(int) round($days->count() * 0.25), max(2, $days->count() - 4)], true)) {
                $emergencyDone++;
                $first = $emergencyDone === 1;
                $context->at($day->setTime(8, 30), fn () => $this->emergency($central, $items, $suppliers, $itemSupplier, $buyer, $manager, $first, $day));
            }
            if ($day->day === 1 && $dayNo > 1) {
                $context->at($day->setTime(8, 0), fn () => $this->suppliers->raiseDocumentAlerts($org, $recipientIds));
            }
        }

        // The very end: the last invoices and deliveries that had not arrived stay open — that is how a real store looks on any given day.
        $open = ProcurementPurchaseOrder::query()->where('organization_id', $org)->whereIn('status', ['issued', 'partially_received', 'pending_approval'])->count();

        return sprintf(
            '%d purchase orders (%d by quotation, %d emergency), %d deliveries (%d lines partly rejected), %d invoices (%d did not match), %d returns to suppliers; %d issues, %d transfers, %d uses at the point of care (%d stock-outs), %d counts, %d write-offs approved; %d orders still open; %d steps skipped',
            $this->summary['pos'], $this->summary['rfqs'], $this->summary['emergency'], $this->summary['received'], $this->summary['rejected_lines'], $this->summary['invoices'], $this->summary['mismatch'], $this->summary['returns'],
            $this->summary['issues'], $this->summary['transfers'], $this->summary['uses'], $this->summary['stockouts'], $this->summary['counts'], $this->summary['writeoffs'], $open, $this->summary['skipped'],
        );
    }

    /** @return array{0: array<string, InventoryStore>, 1: array<string, InventoryItem>, 2: array<string, ProcurementSupplier>, 3: array<string, string>, 4: Collection<int, Department>} */
    private function setup(HistoryContext $context, User $buyer, User $manager, User $buyingManager): array
    {
        $org = $context->organization->id;
        // The departments that ask for supplies; a hospital that has not set any up yet gets the usual ones.
        foreach ([['SURG', 'Surgery'], ['MED', 'Medicine'], ['CAS', 'Casualty'], ['PATH', 'Pathology'], ['ADMIN', 'Administration']] as [$code, $name]) {
            if (Department::query()->where('organization_id', $org)->count() < 4) {
                Department::query()->firstOrCreate(['organization_id' => $org, 'branch_id' => $context->branch->id, 'code' => $code], ['name' => $name, 'is_active' => true]);
            }
        }
        $departments = Department::query()->where('organization_id', $org)->orderBy('id')->limit(6)->get();
        $stores = [];
        foreach ([['CENTRAL', 'Central store', 'central', null], ['OT-STORE', 'Theatre store', 'ot', 'Surgery'], ['WARD-STORE', 'Ward store', 'ward', 'Medicine'], ['HK-STORE', 'Housekeeping store', 'housekeeping', null]] as [$code, $name, $kind, $deptName]) {
            $stores[$code] = InventoryStore::query()->where(['organization_id' => $org, 'code' => $code])->first()
                ?? $this->masters->createStore($org, ['code' => $code, 'name' => $name, 'kind' => $kind, 'branch_id' => $context->branch->id, 'department_id' => $deptName ? $departments->first(fn ($d) => stripos($d->name, $deptName) !== false)?->id : null]);
        }
        foreach (['A1', 'A2', 'B1'] as $i => $bin) {
            $this->masters->addLocation($stores['CENTRAL'], $bin, ['Shelf A1 — consumables', 'Shelf A2 — dressings', 'Bay B1 — linen and bulk'][$i]);
        }
        $this->masters->quarantineStore($org);

        $categories = [];
        foreach (self::CATEGORIES as $code => [$name, $kind]) {
            $categories[$code] = $this->masters->createCategory($org, ['code' => $code, 'name' => $name, 'kind' => $kind]);
        }
        $items = [];
        $itemSupplier = [];
        foreach (self::ITEMS as [$code, $name, $cat, $base, $pack, $perPack, $batch, $cost, $min, $max, $shelf, $billing, $supplierKey]) {
            $items[$code] = $this->masters->createItem($categories[$cat], [
                'code' => $code, 'name' => $name, 'base_uom' => $base, 'purchase_uom' => $pack, 'purchase_to_base' => $perPack, 'tracks_batch' => $batch, 'tracks_expiry' => $batch, 'issue_rule' => 'fefo',
                'reorder_min' => $min, 'reorder_max' => $max, 'lead_time_days' => 5, 'standard_cost' => $cost, 'charge_service_code' => $billing,
            ]);
            $itemSupplier[$code] = $supplierKey;
        }

        $suppliers = [];
        foreach (self::SUPPLIERS as $key => [$code, $name]) {
            $supplier = $this->suppliers->create($org, ['code' => $code, 'name' => $name, 'contact_name' => 'Accounts desk', 'phone' => '01'.str_pad((string) (710000000 + crc32($code) % 289999999), 9, '0', STR_PAD_LEFT), 'email' => strtolower($code).'@suppliers.example.invalid', 'bin_number' => 'BIN-'.(100000 + crc32($code) % 899999), 'payment_terms_days' => $key === 'A' ? 45 : 30]);
            $expiresSoon = $key === 'H'; // one supplier's trade licence ends within the month, for the alert
            $this->suppliers->addDocument($supplier, 'trade_licence', 'TL-'.$code, $context->from->subYear()->toDateString(), ($expiresSoon ? $context->from->addDays(40) : $context->from->addYears(2))->toDateString());
            $this->suppliers->addDocument($supplier, 'vat_bin', 'VAT-'.$code, $context->from->subYear()->toDateString(), null);
            if (in_array($key, ['A', 'G'], true)) {
                $this->suppliers->addDocument($supplier, 'drug_licence', 'DL-'.$code, $context->from->subYear()->toDateString(), $context->from->addYears(1)->toDateString());
            }
            if ($key === 'X') {
                $this->suppliers->qualify($supplier, $buyingManager);
                $this->suppliers->disqualify($supplier, 'Delivered expired goods twice', $buyingManager);
            } else {
                $this->suppliers->qualify($supplier, $buyingManager);
                $this->suppliers->evaluate($supplier, $buyingManager, mt_rand(33, 49) / 10, mt_rand(35, 50) / 10, mt_rand(30, 48) / 10, 'Opening scorecard', $context->from->subDay()->toDateString());
            }
            $suppliers[$key] = $supplier->refresh();
        }
        // agreed prices for the most used items (5% under the standard cost)
        foreach (['GLOVE-M', 'SYRINGE-5', 'IV-SET', 'MASK-SURG', 'GAUZE', 'BEDSHEET', 'PAPER-A4', 'DISINFECT-5L'] as $code) {
            ProcurementContract::create(['organization_id' => $org, 'supplier_id' => $suppliers[$itemSupplier[$code]]->id, 'item_id' => $items[$code]->id, 'unit_price' => number_format((float) $items[$code]->standard_cost * (float) $items[$code]->purchase_to_base * 0.95, 4, '.', ''), 'valid_from' => $context->from->subMonth()->toDateString(), 'valid_to' => null, 'reference' => 'RATE-'.$code]);
        }
        foreach ($departments->take(4) as $department) {
            $this->budgets->set($org, $department->id, $context->from->startOfMonth()->toDateString(), $context->to->endOfMonth()->toDateString(), '4000000');
        }

        return [$stores, $items, $suppliers, $itemSupplier, $departments];
    }

    /** Weekly look at what is low: one requisition per supplier, approved, ordered and sent; once in a while through a request for quotation. */
    private function replenish(HistoryContext $context, InventoryStore $central, array $items, array $itemSupplier, array $suppliers, User $storekeeper, User $manager, User $buyer, User $buyingManager, CarbonImmutable $day, bool $opening): void
    {
        $org = $context->organization->id;
        $wanted = collect($this->reorder->suggestions($org))->groupBy(fn ($r) => $itemSupplier[$r['code']] ?? 'A');
        foreach ($wanted as $supplierKey => $rows) {
            $supplier = $suppliers[$supplierKey];
            try {
                $requisition = $this->requisitions->create($storekeeper, $central, $rows->map(fn ($r) => ['item_id' => $r['item_id'], 'quantity' => $r['suggested_purchase_units']])->values()->all(), ['notes' => $opening ? 'Opening stock' : 'Weekly reorder', 'department_id' => $central->department_id]);
                $this->requisitions->submit($requisition, $storekeeper);
                $this->approveRequisition($requisition->refresh(), $manager, $buyingManager);

                $order = null;
                $second = $supplierKey === 'A' ? $suppliers['H'] : $suppliers['G'];
                if (! $opening && $this->chanceOf(18) && $second->refresh()->status === 'qualified') {
                    $rfq = $this->rfqs->createFromRequisition($requisition->refresh(), [$supplier, $second], $buyer);
                    foreach ([$supplier, $second] as $i => $quoting) {
                        $prices = $rfq->lines->mapWithKeys(fn ($l) => [$l->id => number_format((float) app(ProcurementPricing::class)->unitPrice($l->item, $quoting->id) * (mt_rand(94, 108) / 100), 4, '.', '')])->all();
                        $this->rfqs->recordQuotation($rfq, $quoting, $prices, mt_rand(2, 6));
                    }
                    $best = collect($this->rfqs->comparison($rfq))->first();
                    $winner = $best['supplier_id'] === $supplier->id ? $supplier : $second;
                    $order = $this->rfqs->award($rfq->refresh(), $winner, $buyer, $central);
                    $this->summary['rfqs']++;
                } else {
                    $order = $this->orders->create($buyer, $supplier, $central, $requisition->lines->map(fn ($l) => ['item_id' => $l->item_id, 'quantity' => $l->quantity])->all(), ['requisition_id' => $requisition->id, 'department_id' => $central->department_id]);
                }
                $this->approveAndIssueOrder($order, $buyer, $manager, $buyingManager);
                $this->summary['pos']++;
                $due = $day->addDays(mt_rand(2, 4))->toDateString();
                $this->deliveries[$due][$order->id] = 1;
            } catch (ValidationException) {
                $this->summary['skipped']++;
            }
        }
    }

    private function approveRequisition($requisition, User $level1, User $level2): void
    {
        foreach ([$level1, $level2] as $approver) {
            if ($requisition->refresh()->status !== 'submitted') {
                break;
            }
            $this->requisitions->approve($requisition, $approver);
        }
    }

    private function approveAndIssueOrder(ProcurementPurchaseOrder $order, User $buyer, User $level1, User $level2): void
    {
        $this->orders->submit($order, $buyer);
        foreach ([$level1, $level2] as $approver) {
            if ($order->refresh()->status !== 'pending_approval') {
                break;
            }
            $this->orders->approve($order, $approver);
        }
        $this->orders->issue($order->refresh(), $buyer);
    }

    /** Goods arrive: usually in full, one time in four only a part (the rest follows). Inspected the same afternoon. */
    private function deliver(HistoryContext $context, int $poId, int $attempt, User $storekeeper, CarbonImmutable $day): void
    {
        $order = ProcurementPurchaseOrder::with('lines.item')->find($poId);
        if (! $order || ! in_array($order->status, ['issued', 'partially_received'], true)) {
            return;
        }
        $partial = $attempt === 1 && $this->chanceOf(25);
        $lines = [];
        foreach ($order->lines->where('closed', false) as $line) {
            $due = bcsub((string) $line->quantity, (string) $line->received_quantity, 4);
            $quantity = $partial ? (string) max(1, (int) floor((float) $due * 0.6)) : $due;
            $shelf = $this->shelfMonths($line->item->code);
            // now and then a short-dated delivery, which receiving refuses
            $months = $line->item->tracks_expiry ? ($this->chanceOf(3) ? 0 : max(2, $shelf - mt_rand(0, 6))) : 0;
            $expiry = $line->item->tracks_expiry ? ($months === 0 ? $day->addDays(12) : $day->addMonths($months))->toDateString() : null;
            $lines[] = ['po_line_id' => $line->id, 'quantity' => $quantity, 'batch_no' => $line->item->tracks_batch ? 'B'.$day->format('ymd').'-'.$line->id : null, 'expiry_date' => $expiry];
        }
        if ($lines === []) {
            return;
        }
        try {
            $receipt = $this->receipts->receive($order, $storekeeper, $lines, 'DN-'.$day->format('ymd').'-'.$order->id, $day->toDateString());
            $decisions = [];
            if ($this->chanceOf(12)) {
                $line = $receipt->lines->first();
                $rejected = max(1, (int) floor((float) $line->received_quantity * 0.2));
                $decisions[$line->id] = ['rejected' => $rejected, 'accepted' => bcsub((string) $line->received_quantity, (string) $rejected, 4), 'reason' => $this->pickOf(['Damaged outer cartons', 'Wrong product supplied', 'Seals broken', 'Temperature indicator tripped'])];
                $this->summary['rejected_lines']++;
            }
            $context->at($day->setTime(14, 0), fn () => $this->receipts->inspect($receipt, $storekeeper, $decisions));
            $this->summary['received']++;
        } catch (ValidationException) {
            $this->summary['skipped']++;

            return;
        }

        $order->refresh();
        if ($order->status === 'partially_received') {
            $this->deliveries[$day->addDays(mt_rand(2, 4))->toDateString()][$order->id] = $attempt + 1;
        } elseif ($order->status === 'received') {
            $this->invoicesDue[$day->addDays(mt_rand(3, 9))->toDateString()][] = $order->id;
        }
    }

    /** The supplier's invoice: usually right; sometimes the price has crept up or more is billed than arrived. */
    private function invoice(HistoryContext $context, int $poId, User $buyer, User $buyingManager): void
    {
        $order = ProcurementPurchaseOrder::with('lines')->find($poId);
        if (! $order) {
            return;
        }
        $roll = mt_rand(1, 100);
        $lines = $order->lines->map(fn ($l) => [
            'po_line_id' => $l->id,
            'quantity' => $roll > 94 ? bcadd((string) $l->received_quantity, '2', 4) : (string) $l->received_quantity,
            'unit_price' => $roll <= 6 ? bcmul((string) $l->unit_price, '1.08', 4) : (string) $l->unit_price,
        ])->filter(fn ($l) => bccomp($l['quantity'], '0', 4) > 0)->values()->all();
        if ($lines === []) {
            return;
        }
        try {
            $invoice = $this->matching->record($buyer, $order->supplier, $order, 'SI-'.$order->number.'-'.mt_rand(100, 999), now()->toDateString(), $lines);
            $this->summary['invoices']++;
            if ($invoice->status === 'mismatch') {
                $this->summary['mismatch']++;
                $invoice = $this->chanceOf(70)
                    ? $this->matching->approveMismatch($invoice, $buyingManager, 'Price difference agreed with the supplier')
                    : $this->matching->reject($invoice, $buyingManager, 'Billed for more than was delivered; credit note asked for');
            }
            in_array($invoice->status, ['matched', 'approved'], true) && $this->matching->handToFinance($invoice);
        } catch (ValidationException) {
            $this->summary['skipped']++;
        }
    }

    private function departmentRequests(HistoryContext $context, InventoryStore $central, array $items, $departments, User $storekeeper, User $manager): void
    {
        $codes = ['GLOVE-M', 'SYRINGE-5', 'IV-SET', 'MASK-SURG', 'GAUZE', 'SPIRIT-SWAB', 'BEDSHEET', 'PILLOWCASE', 'GOWN-PATIENT', 'TOWEL', 'WRISTBAND', 'PAPER-A4'];
        foreach ($departments->take(3) as $department) {
            try {
                $picked = collect($codes)->shuffle()->take(mt_rand(2, 4));
                $request = $this->issues->request($storekeeper, $department, $central, $picked->map(fn ($c) => ['item_id' => $items[$c]->id, 'quantity' => mt_rand(2, 8) * 10])->all());
                $this->issues->issueRequest($request, $manager);
                $this->summary['issues']++;
            } catch (ValidationException) {
                $this->summary['stockouts']++;
            }
        }
        // someone returns a little of what they did not use
        if ($this->chanceOf(20)) {
            $issue = InventoryIssue::query()->where('organization_id', $context->organization->id)->latest('id')->first();
            $line = $issue?->lines()->first();
            if ($line) {
                try {
                    $this->issues->returnLine($line, $storekeeper, min((float) $line->quantity, 5), 'Not needed');
                } catch (ValidationException) {
                    $this->summary['skipped']++;
                }
            }
        }
    }

    /** Weekly: the theatre and ward stores are topped up from the central store. */
    private function topUp(HistoryContext $context, InventoryStore $central, array $stores, array $items, User $storekeeper): void
    {
        $sets = ['WARD-STORE' => ['CANNULA-20G' => 60, 'CANNULA-22G' => 40, 'DRESSING-PACK' => 40, 'CATHETER-FOLEY' => 12, 'IV-SET' => 50, 'GLOVE-M' => 200], 'OT-STORE' => ['SUTURE-VIC' => 40, 'SUTURE-SILK' => 30, 'DRESSING-PACK' => 30, 'GLOVE-M' => 200, 'MASK-SURG' => 150]];
        foreach ($sets as $code => $lines) {
            try {
                $lines = collect($lines)->filter(fn ($q, $c) => bccomp(app(InventoryStockService::class)->usable($central, $items[$c]), (string) $q, 4) >= 0);
                if ($lines->isEmpty()) {
                    continue;
                }
                $transfer = $this->transfers->request($storekeeper, $central, $stores[$code], $lines->map(fn ($q, $c) => ['item_id' => $items[$c]->id, 'quantity' => $q])->values()->all());
                $this->transfers->dispatch($transfer, $storekeeper);
                $this->transfers->receive($transfer->refresh(), $storekeeper);
                $this->summary['transfers']++;
            } catch (ValidationException) {
                $this->summary['skipped']++;
            }
        }
    }

    /** @param  array{id: int, patient_id: int, encounter_id: int|null, at: string}  $admission */
    private function useForPatient(HistoryContext $context, array $admission, array $stores, array $items, User $user): void
    {
        $dept = $stores['WARD-STORE']->department_id;
        $uses = [['WARD-STORE', 'CANNULA-20G', mt_rand(1, 2), 80], ['WARD-STORE', 'DRESSING-PACK', mt_rand(1, 3), 55], ['WARD-STORE', 'CATHETER-FOLEY', 1, 15], ['OT-STORE', 'SUTURE-VIC', mt_rand(1, 3), 12], ['OT-STORE', 'DRESSING-PACK', 1, 12]];
        foreach ($uses as [$store, $code, $qty, $chance]) {
            if (! $this->chanceOf($chance)) {
                continue;
            }
            try {
                $this->consumption->record($user, $stores[$store], $items[$code], $qty, [
                    'department_id' => $dept, 'patient_id' => $admission['patient_id'], 'encounter_id' => $admission['encounter_id'], 'admission_id' => $admission['id'], 'source_module' => $store === 'OT-STORE' ? 'ot' : 'ipd',
                ]);
                $this->summary['uses']++;
            } catch (ValidationException) {
                $this->summary['stockouts']++;
            }
        }
    }

    private function housekeepingUse(HistoryContext $context, array $stores, array $items, $departments, User $user): void
    {
        if (! $this->chanceOf(35)) {
            return;
        }
        $hk = $stores['HK-STORE'];
        try {
            // housekeeping draws its stock from the central store on a request, then uses it up
            $central = $stores['CENTRAL'];
            foreach (['DISINFECT-5L' => 6, 'BIN-LINER' => 10, 'HAND-SOAP' => 6] as $code => $qty) {
                if (bccomp(app(InventoryStockService::class)->onHand($hk, $items[$code]), (string) $qty, 4) < 0 && bccomp(app(InventoryStockService::class)->usable($central, $items[$code]), '40', 4) >= 0) {
                    $transfer = $this->transfers->request($user, $central, $hk, [['item_id' => $items[$code]->id, 'quantity' => 30]]);
                    $this->transfers->dispatch($transfer, $user);
                    $this->transfers->receive($transfer->refresh(), $user);
                    $this->summary['transfers']++;
                }
                $this->consumption->record($user, $hk, $items[$code], $qty, ['department_id' => $departments->first()?->id]);
                $this->summary['uses']++;
            }
        } catch (ValidationException) {
            $this->summary['stockouts']++;
        }
    }

    private function sendBack(HistoryContext $context, User $buyer, CarbonImmutable $day): void
    {
        $org = $context->organization->id;
        $qrn = InventoryStore::where(['organization_id' => $org, 'kind' => 'quarantine'])->first();
        if (! $qrn) {
            return;
        }
        $grouped = InventoryStock::query()->where('store_id', $qrn->id)->where('quantity_on_hand', '>', 0)->get()
            ->filter(fn ($s) => InventoryBatch::where('id', $s->batch_id)->where('status', 'quarantined')->whereNotNull('supplier_id')->exists())
            ->groupBy(fn ($s) => InventoryBatch::find($s->batch_id)->supplier_id);
        foreach ($grouped as $supplierId => $rows) {
            try {
                $return = $this->returns->create($buyer, ProcurementSupplier::findOrFail($supplierId), $qrn, $rows->map(fn ($s) => ['item_id' => $s->item_id, 'batch_id' => $s->batch_id, 'quantity' => (string) $s->quantity_on_hand])->all(), 'Rejected at inspection');
                $this->returns->dispatch($return, $buyer);
                $this->creditsDue[$day->addDays(4)->toDateString()][] = $return->id;
                $this->summary['returns']++;
            } catch (ValidationException) {
                $this->summary['skipped']++;
            }
        }
    }

    private function count(HistoryContext $context, InventoryStore $central, User $storekeeper, User $manager): void
    {
        try {
            $ids = InventoryStock::query()->where('store_id', $central->id)->where('quantity_on_hand', '>', 0)->pluck('item_id')->unique()->shuffle()->take(8)->values()->all();
            if ($ids === []) {
                return;
            }
            $count = $this->counts->start($storekeeper, $central, $ids);
            foreach ($count->lines as $line) {
                $roll = mt_rand(1, 100);
                $system = (float) $line->system_quantity;
                $counted = $roll <= 70 ? $system : ($roll <= 90 ? max(0, $system - mt_rand(1, 3)) : $system + mt_rand(1, 2));
                $this->counts->enter($line, $counted, $counted === $system ? null : ($counted < $system ? 'Short on the shelf; opened pack not recorded' : 'Found stock put away in the wrong bay'));
            }
            $this->counts->submit($count->refresh(), $storekeeper);
            $this->counts->approve($count->refresh(), $manager);
            $this->summary['counts']++;
        } catch (ValidationException) {
            $this->summary['skipped']++;
        }
    }

    /** @param  array<string, ProcurementSupplier>  $suppliers */
    private function score(array $suppliers, User $by, array $recipientIds, int $org): void
    {
        foreach ($suppliers as $key => $supplier) {
            if ($supplier->refresh()->status !== 'qualified' || $key === 'X') {
                continue;
            }
            try {
                $this->suppliers->evaluate($supplier, $by, mt_rand(30, 50) / 10, mt_rand(32, 50) / 10, mt_rand(30, 48) / 10, 'Monthly review');
            } catch (ValidationException) {
                $this->summary['skipped']++;
            }
        }
    }

    private function hold(InventoryStore $central, User $manager, CarbonImmutable $day): void
    {
        $stock = InventoryStock::query()->where('store_id', $central->id)->where('quantity_on_hand', '>', 20)->where('batch_id', '>', 0)->first();
        $batch = $stock ? InventoryBatch::where(['id' => $stock->batch_id, 'status' => 'active'])->first() : null;
        if ($batch) {
            $this->expiry->quarantine($batch, $manager, 'Cold-storage fault in the bay; held until checked');
            $this->releasesDue[$day->addDays(3)->toDateString()] = $batch->id;
        }
    }

    private function release(int $batchId, User $manager): void
    {
        $batch = InventoryBatch::find($batchId);
        if ($batch && $batch->status === 'quarantined') {
            $this->expiry->release($batch, $manager, 'Checked: packaging intact and in specification');
        }
    }

    /** @param  array<string, InventoryItem>  $items */
    private function recall(array $items, User $manager): void
    {
        $item = $items['SYRINGE-10'];
        $batchNo = InventoryBatch::where('item_id', $item->id)->where('status', 'active')->orderBy('id')->value('batch_no');
        if ($batchNo) {
            $this->expiry->recall($item, $batchNo, 'Manufacturer notice: plunger seal defect in this lot', $manager);
        }
    }

    private function emergency(InventoryStore $central, array $items, array $suppliers, array $itemSupplier, User $buyer, User $manager, bool $ratify, CarbonImmutable $day): void
    {
        try {
            $code = $ratify ? 'MASK-N95' : 'CANNULA-20G';
            $order = $this->orders->createEmergency($buyer, $suppliers[$itemSupplier[$code]], $central, [['item_id' => $items[$code]->id, 'quantity' => 8]], $ratify ? 'Outbreak ward ran short of respirators' : 'Theatre list brought forward; cannulas ran out');
            $this->summary['emergency']++;
            $this->summary['pos']++;
            $this->deliveries[$day->addDay()->toDateString()][$order->id] = 2;
            if ($ratify) {
                $this->orders->ratify($order->refresh(), $manager);
            }
        } catch (ValidationException) {
            $this->summary['skipped']++;
        }
    }

    private function shelfMonths(string $code): int
    {
        foreach (self::ITEMS as $row) {
            if ($row[0] === $code) {
                return (int) ($row[10] ?? 24);
            }
        }

        return 24;
    }

    private function chanceOf(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }

    /** @param  list<string>  $options */
    private function pickOf(array $options): string
    {
        return $options[mt_rand(0, count($options) - 1)];
    }
}
