<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\ProcurementRequisition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** A department or store asks procurement to buy (S04). Approval levels depend on the estimated value. */
class RequisitionService
{
    public function __construct(private readonly InventoryNumbers $numbers, private readonly ApprovalService $approvals, private readonly ProcurementPricing $pricing) {}

    /**
     * @param  list<array{item_id: int, quantity: float|int|string, estimated_unit_price?: float|int|string|null}>  $lines  quantities in PURCHASE units
     * @param  array{department_id?: ?int, priority?: string, needed_by?: ?string, notes?: ?string, branch_id?: ?int}  $options
     */
    public function create(User $by, InventoryStore $store, array $lines, array $options = []): ProcurementRequisition
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'Add at least one item.']);
        }
        $priority = $options['priority'] ?? 'routine';
        if (! in_array($priority, ['routine', 'urgent'], true)) {
            throw ValidationException::withMessages(['priority' => 'Use routine or urgent.']);
        }

        return DB::transaction(function () use ($by, $store, $lines, $options, $priority) {
            $requisition = ProcurementRequisition::create([
                'organization_id' => $store->organization_id, 'branch_id' => $options['branch_id'] ?? $store->branch_id, 'department_id' => $options['department_id'] ?? $store->department_id, 'store_id' => $store->id,
                'number' => $this->numbers->next('requisition', $store->organization_id, $by), 'priority' => $priority, 'needed_by' => $options['needed_by'] ?? null, 'notes' => $options['notes'] ?? null, 'requested_by' => $by->id,
            ]);
            $total = '0.00';
            foreach ($lines as $line) {
                $item = InventoryItem::query()->where('organization_id', $store->organization_id)->findOrFail($line['item_id']);
                if (bccomp((string) $line['quantity'], '0', 4) <= 0) {
                    throw ValidationException::withMessages(['quantity' => 'Quantities must be above zero.']);
                }
                $price = $line['estimated_unit_price'] ?? $this->pricing->unitPrice($item);
                $requisition->lines()->create(['item_id' => $item->id, 'quantity' => $line['quantity'], 'estimated_unit_price' => $price]);
                $total = bcadd($total, bcmul((string) $line['quantity'], (string) $price, 2), 2);
            }
            $requisition->update(['estimated_total' => $total]);

            return $requisition->refresh();
        });
    }

    public function submit(ProcurementRequisition $requisition, User $by): ProcurementRequisition
    {
        $this->must($requisition, ['draft'], 'submit');
        DB::transaction(function () use ($requisition) {
            $this->approvals->begin($requisition->organization_id, 'requisition', $requisition->id, (string) $requisition->estimated_total);
            $requisition->update(['status' => 'submitted', 'submitted_at' => now()]);
        });

        return $requisition->refresh();
    }

    public function approve(ProcurementRequisition $requisition, User $by, ?string $comment = null): ProcurementRequisition
    {
        $this->must($requisition, ['submitted'], 'approve');
        if ($this->approvals->approve('requisition', $requisition->id, $by, $requisition->requested_by, $comment)) {
            $requisition->update(['status' => 'approved']);
        }

        return $requisition->refresh();
    }

    public function reject(ProcurementRequisition $requisition, User $by, string $reason): ProcurementRequisition
    {
        $this->must($requisition, ['submitted'], 'reject');
        $this->approvals->reject('requisition', $requisition->id, $by, $reason);
        $requisition->update(['status' => 'rejected']);

        return $requisition->refresh();
    }

    public function cancel(ProcurementRequisition $requisition): ProcurementRequisition
    {
        $this->must($requisition, ['draft', 'submitted', 'approved'], 'cancel');
        $requisition->update(['status' => 'cancelled']);

        return $requisition->refresh();
    }

    /** @param  list<string>  $allowed */
    private function must(ProcurementRequisition $requisition, array $allowed, string $action): void
    {
        if (! in_array($requisition->status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => "A {$requisition->status} requisition cannot be {$action}d."]);
        }
    }
}
