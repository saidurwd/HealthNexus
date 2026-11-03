<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\ProcurementPoLine;
use App\Models\Inventory\ProcurementPurchaseOrder;
use App\Models\Inventory\ProcurementSupplier;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Purchase orders (S06): approval by amount, versioned amendments, emergency orders approved after the fact (S11). */
class PurchaseOrderService
{
    public function __construct(
        private readonly InventoryNumbers $numbers,
        private readonly ApprovalService $approvals,
        private readonly SupplierService $suppliers,
        private readonly BudgetService $budgets,
        private readonly ProcurementPricing $pricing,
        private readonly InventoryPolicy $policy,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  list<array{item_id: int, quantity: float|int|string, unit_price?: float|int|string|null, tax_percent?: float|int|string}>  $lines  PURCHASE units and price per purchase unit
     * @param  array{department_id?: ?int, requisition_id?: ?int, rfq_id?: ?int, expected_on?: ?string, terms?: ?string, po_type?: string, order_date?: ?string}  $options
     */
    public function create(User $by, ProcurementSupplier $supplier, InventoryStore $store, array $lines, array $options = []): ProcurementPurchaseOrder
    {
        $this->suppliers->assertCanOrder($supplier);
        if ($supplier->organization_id !== $store->organization_id) {
            throw ValidationException::withMessages(['supplier' => 'That supplier belongs to another organization.']);
        }
        $type = $options['po_type'] ?? 'standard';
        if (! in_array($type, ['standard', 'emergency', 'consignment'], true)) {
            throw ValidationException::withMessages(['po_type' => 'Not a known order type.']);
        }

        return DB::transaction(function () use ($by, $supplier, $store, $lines, $options, $type) {
            $order = ProcurementPurchaseOrder::create([
                'organization_id' => $store->organization_id, 'branch_id' => $store->branch_id, 'supplier_id' => $supplier->id, 'store_id' => $store->id, 'department_id' => $options['department_id'] ?? $store->department_id,
                'requisition_id' => $options['requisition_id'] ?? null, 'rfq_id' => $options['rfq_id'] ?? null, 'number' => $this->numbers->next('po', $store->organization_id, $by), 'po_type' => $type,
                'order_date' => $options['order_date'] ?? now()->toDateString(), 'expected_on' => $options['expected_on'] ?? null, 'terms' => $options['terms'] ?? null, 'created_by' => $by->id,
            ]);
            $this->writeLines($order, $lines);

            return $order->refresh();
        });
    }

    public function submit(ProcurementPurchaseOrder $order, User $by): ProcurementPurchaseOrder
    {
        $this->move($order, 'pending_approval');
        $this->suppliers->assertCanOrder($order->supplier);
        $this->budgets->assertWithin($order->organization_id, $order->department_id, (string) $order->total, $order->id, $order->order_date->toDateString());
        $this->approvals->begin($order->organization_id, 'purchase_order', $order->id, (string) $order->total);
        $order->update(['status' => 'pending_approval']);

        return $order->refresh();
    }

    public function approve(ProcurementPurchaseOrder $order, User $by, ?string $comment = null): ProcurementPurchaseOrder
    {
        if ($order->status !== 'pending_approval') {
            throw ValidationException::withMessages(['status' => "A {$order->status} order is not waiting for approval."]);
        }
        if ($this->approvals->approve('purchase_order', $order->id, $by, $order->created_by, $comment)) {
            $order->update(['status' => $this->afterApproval($order)]);
        }

        return $order->refresh();
    }

    public function reject(ProcurementPurchaseOrder $order, User $by, string $reason): ProcurementPurchaseOrder
    {
        if ($order->status !== 'pending_approval') {
            throw ValidationException::withMessages(['status' => "A {$order->status} order is not waiting for approval."]);
        }
        $this->approvals->reject('purchase_order', $order->id, $by, $reason);
        $order->update(['status' => $order->issued_at ? $this->afterApproval($order) : 'draft']);

        return $order->refresh();
    }

    public function issue(ProcurementPurchaseOrder $order, User $by): ProcurementPurchaseOrder
    {
        $this->move($order, 'issued');
        $this->suppliers->assertCanOrder($order->supplier);
        $order->update(['status' => 'issued', 'issued_at' => now()]);
        $this->audit->log('PO_ISSUED', ProcurementPurchaseOrder::class, $order->id, null, ['number' => $order->number, 'total' => (string) $order->total]);

        return $order->refresh();
    }

    /**
     * Changes an order that has been approved or sent. The earlier version is kept whole; a change that raises the value past an approval
     * level the order has not had sends it back for that approval, and goods cannot be received meanwhile.
     *
     * @param  list<array{line_id?: int, item_id?: int, quantity?: float|int|string, unit_price?: float|int|string, remove?: bool}>  $changes
     */
    public function amend(ProcurementPurchaseOrder $order, User $by, array $changes, string $reason): ProcurementPurchaseOrder
    {
        if (! in_array($order->status, ['approved', 'issued', 'partially_received'], true)) {
            throw ValidationException::withMessages(['status' => "A {$order->status} order cannot be amended."]);
        }

        return DB::transaction(function () use ($order, $by, $changes, $reason) {
            $order = ProcurementPurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);
            $order->amendments()->create(['version' => $order->version, 'snapshot' => $this->snapshot($order), 'reason' => $reason, 'amended_by' => $by->id]);

            foreach ($changes as $change) {
                if (isset($change['line_id'])) {
                    $line = ProcurementPoLine::query()->where('purchase_order_id', $order->id)->findOrFail($change['line_id']);
                    if (! empty($change['remove'])) {
                        if (bccomp((string) $line->received_quantity, '0', 4) > 0 || bccomp((string) $line->rejected_quantity, '0', 4) > 0) {
                            throw ValidationException::withMessages(['lines' => 'A line that has been received cannot be removed; reduce it to what arrived.']);
                        }
                        $line->delete();

                        continue;
                    }
                    $quantity = $change['quantity'] ?? $line->quantity;
                    if (bccomp((string) $quantity, (string) $line->received_quantity, 4) < 0) {
                        throw ValidationException::withMessages(['quantity' => 'You cannot order less than has already been received.']);
                    }
                    $price = $change['unit_price'] ?? $line->unit_price;
                    $line->update(['quantity' => $quantity, 'unit_price' => $price, 'line_total' => bcmul((string) $quantity, (string) $price, 2), 'closed' => bccomp((string) $line->received_quantity, (string) $quantity, 4) >= 0]);
                } else {
                    $this->addLine($order, $change);
                }
            }
            $this->recalculate($order);
            $order->update(['version' => $order->version + 1]);
            $this->budgets->assertWithin($order->organization_id, $order->department_id, (string) $order->total, $order->id, $order->order_date->toDateString());

            if ($this->approvals->extend($order->organization_id, 'purchase_order', $order->id, (string) $order->total)) {
                $order->update(['status' => 'pending_approval']);
            }
            $this->audit->log('PO_AMENDED', ProcurementPurchaseOrder::class, $order->id, null, ['version' => $order->version, 'reason' => $reason]);

            return $order->refresh();
        });
    }

    public function cancel(ProcurementPurchaseOrder $order, string $reason): ProcurementPurchaseOrder
    {
        if ($order->lines()->where(fn ($q) => $q->where('received_quantity', '>', 0)->orWhere('rejected_quantity', '>', 0))->exists()) {
            throw ValidationException::withMessages(['status' => 'Goods have already arrived on this order; close it instead.']);
        }
        $this->move($order, 'cancelled');
        $order->update(['status' => 'cancelled', 'cancel_reason' => $reason]);

        return $order->refresh();
    }

    /** Stops waiting for what has not arrived. */
    public function close(ProcurementPurchaseOrder $order): ProcurementPurchaseOrder
    {
        $this->move($order, 'closed');
        $order->lines()->update(['closed' => true]);
        $order->update(['status' => 'closed']);

        return $order->refresh();
    }

    /**
     * Urgent buying (S11): goes out at once, but is approved afterwards by the same chain, inside a time limit. It is a controlled path,
     * not a bypass: the supplier must still be qualified, goods are still received and inspected, the invoice is still matched.
     *
     * @param  list<array{item_id: int, quantity: float|int|string, unit_price?: float|int|string|null}>  $lines
     * @param  array<string, mixed>  $options
     */
    public function createEmergency(User $by, ProcurementSupplier $supplier, InventoryStore $store, array $lines, string $reason, array $options = []): ProcurementPurchaseOrder
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Say why this cannot wait for the normal route.']);
        }
        $order = $this->create($by, $supplier, $store, $lines, ['po_type' => 'emergency'] + $options);
        if (bccomp((string) $order->total, (string) $this->policy->get('emergency_max_amount'), 2) > 0) {
            throw ValidationException::withMessages(['total' => 'An emergency order may not exceed '.$this->policy->get('emergency_max_amount').'; use the normal route.']);
        }
        $this->budgets->assertWithin($order->organization_id, $order->department_id, (string) $order->total, $order->id);
        $this->approvals->begin($order->organization_id, 'purchase_order', $order->id, (string) $order->total);
        $order->update(['status' => 'issued', 'issued_at' => now(), 'emergency_reason' => $reason, 'ratify_by' => now()->addHours((int) $this->policy->get('emergency_ratify_hours'))]);
        $this->audit->log('PO_EMERGENCY', ProcurementPurchaseOrder::class, $order->id, null, ['reason' => $reason, 'total' => (string) $order->total]);

        return $order->refresh();
    }

    /** One step of the after-the-fact approval. */
    public function ratify(ProcurementPurchaseOrder $order, User $by, ?string $comment = null): ProcurementPurchaseOrder
    {
        if ($order->po_type !== 'emergency' || $order->ratified_at) {
            throw ValidationException::withMessages(['status' => 'This order does not need ratifying.']);
        }
        if ($this->approvals->approve('purchase_order', $order->id, $by, $order->created_by, $comment)) {
            $order->update(['ratified_at' => now()]);
        }

        return $order->refresh();
    }

    /** Refusing to ratify stops what has not arrived yet. */
    public function refuseRatification(ProcurementPurchaseOrder $order, User $by, string $reason): ProcurementPurchaseOrder
    {
        $this->approvals->reject('purchase_order', $order->id, $by, $reason);
        $received = $order->lines()->where('received_quantity', '>', 0)->exists();
        $order->update(['status' => $received ? 'closed' : 'cancelled', 'cancel_reason' => Str::limit('Emergency order not ratified: '.$reason, 300, '')]);
        $order->lines()->update(['closed' => true]);

        return $order->refresh();
    }

    /** @return Collection<int, ProcurementPurchaseOrder> emergency orders past their ratification time */
    public function overdueRatifications(int $organizationId)
    {
        return ProcurementPurchaseOrder::query()->where('organization_id', $organizationId)->where('po_type', 'emergency')->whereNull('ratified_at')
            ->whereNotIn('status', ['cancelled'])->where('ratify_by', '<', now())->whereNotExists(fn ($q) => $q->selectRaw('1')->from('procurement_approvals as a')->whereColumn('a.document_id', 'procurement_purchase_orders.id')->where('a.document_type', 'purchase_order')->where('a.status', 'rejected'))->get();
    }

    /** Called after goods are posted: moves the order to partially_received / received and closes finished lines. */
    public function refreshReceiptStatus(ProcurementPurchaseOrder $order): ProcurementPurchaseOrder
    {
        $order->refresh();
        foreach ($order->lines as $line) {
            if (! $line->closed && bccomp((string) $line->received_quantity, (string) $line->quantity, 4) >= 0) {
                $line->update(['closed' => true]);
            }
        }
        $order->load('lines');
        $anyReceived = $order->lines->contains(fn ($l) => bccomp((string) $l->received_quantity, '0', 4) > 0);
        $allDone = $order->lines->every(fn ($l) => $l->closed);
        if (in_array($order->status, ['issued', 'partially_received'], true)) {
            $order->update(['status' => $allDone ? 'received' : ($anyReceived ? 'partially_received' : $order->status)]);
        }

        return $order->refresh();
    }

    /** @param  list<array<string, mixed>>  $lines */
    private function writeLines(ProcurementPurchaseOrder $order, array $lines): void
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'Add at least one item.']);
        }
        foreach ($lines as $line) {
            $this->addLine($order, $line);
        }
        $this->recalculate($order);
    }

    /** @param  array<string, mixed>  $line */
    private function addLine(ProcurementPurchaseOrder $order, array $line): void
    {
        $item = InventoryItem::query()->where('organization_id', $order->organization_id)->findOrFail($line['item_id']);
        $quantity = (string) ($line['quantity'] ?? 0);
        $price = (string) ($line['unit_price'] ?? $this->pricing->unitPrice($item, $order->supplier_id));
        if (bccomp($quantity, '0', 4) <= 0 || bccomp($price, '0', 4) <= 0) {
            throw ValidationException::withMessages(['lines' => "{$item->name}: quantity and price must be above zero."]);
        }
        $order->lines()->create(['item_id' => $item->id, 'quantity' => $quantity, 'unit_price' => $price, 'tax_percent' => $line['tax_percent'] ?? 0, 'line_total' => bcmul($quantity, $price, 2)]);
    }

    private function recalculate(ProcurementPurchaseOrder $order): void
    {
        $subtotal = '0.00';
        $tax = '0.00';
        foreach ($order->lines()->get() as $line) {
            $subtotal = bcadd($subtotal, (string) $line->line_total, 2);
            $tax = bcadd($tax, bcdiv(bcmul((string) $line->line_total, (string) $line->tax_percent, 4), '100', 2), 2);
        }
        $order->update(['subtotal' => $subtotal, 'tax_amount' => $tax, 'total' => bcadd($subtotal, $tax, 2)]);
    }

    private function afterApproval(ProcurementPurchaseOrder $order): string
    {
        if (! $order->issued_at) {
            return 'approved';
        }

        return $order->lines()->where('received_quantity', '>', 0)->exists() ? 'partially_received' : 'issued';
    }

    private function move(ProcurementPurchaseOrder $order, string $to): void
    {
        $allowed = (array) (config('inventory.po_transitions')[$order->status] ?? []);
        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages(['status' => "An order that is {$order->status} cannot become {$to}."]);
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(ProcurementPurchaseOrder $order): array
    {
        return [
            'number' => $order->number, 'version' => $order->version, 'status' => $order->status, 'subtotal' => (string) $order->subtotal, 'tax_amount' => (string) $order->tax_amount, 'total' => (string) $order->total,
            'lines' => $order->lines()->get()->map(fn ($l) => ['id' => $l->id, 'item_id' => $l->item_id, 'quantity' => (string) $l->quantity, 'unit_price' => (string) $l->unit_price, 'received' => (string) $l->received_quantity])->all(),
        ];
    }
}
