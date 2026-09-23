<?php

namespace App\Services\Pharmacy;

use App\Events\Pharmacy\DispensingCompleted;
use App\Events\Pharmacy\MedicationDispensed;
use App\Models\Billing\BillingItem;
use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyDispensing;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyOrder;
use App\Models\Pharmacy\PharmacyOrderItem;
use App\Models\Pharmacy\PharmacyStockTransaction;
use App\Models\Pharmacy\PharmacyStore;
use App\Models\User;
use App\Services\Billing\ChargeService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The dispensing transactional core (spec §88). Safety checks run — and any resulting alerts are
 * persisted — for every line BEFORE the stock-mutating transaction opens: alert rows must survive
 * even when dispensing is blocked, and nesting them inside the same DB::transaction() as the stock
 * deduction would roll the alert back too (Laravel's savepoint-based nested transactions undo
 * everything, including an inner "commit", the moment the outer transaction rolls back). Once every
 * line clears, FEFO-select or accept an explicit batch, lockForUpdate() the touched pharmacy_stock
 * row, deduct, record the stock and controlled-drug ledger, create the dispensing + item, update
 * order-item quantity (supports partial dispensing — an item is never marked fully dispensed
 * early), charge the actual dispensed quantity via ChargeService::createManual(), then dispatch events.
 */
class PharmacyDispensingService
{
    public function __construct(
        private readonly PharmacyNumberGenerator $numbers,
        private readonly PharmacyBatchSelectionService $batchSelection,
        private readonly PharmacyStockService $stock,
        private readonly PharmacyDispensingSafetyService $safety,
        private readonly PharmacyOrderLifecycleService $orderLifecycle,
        private readonly PharmacyControlledDrugService $controlledDrugs,
        private readonly ChargeService $charges,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @param  array<int, array{order_item_id:int, quantity:int, medication_id?:int|null, batch_id?:int|null, substitution_reason?:string|null, substitution_approved_by?:int|null}>  $lines
     */
    public function dispense(PharmacyOrder $order, PharmacyStore $store, array $lines, User $pharmacist): PharmacyDispensing
    {
        if (in_array($order->status, ['cancelled', 'rejected', 'fully_dispensed'], true)) {
            throw ValidationException::withMessages(['order' => "This order is '{$order->status}' and cannot be dispensed against."]);
        }

        if (empty($lines)) {
            throw ValidationException::withMessages(['lines' => 'At least one line must be dispensed.']);
        }

        $order->loadMissing('items', 'patient');

        $prepared = array_map(fn (array $line) => $this->prepareLine($order, $line), $lines);

        return DB::transaction(function () use ($order, $store, $prepared, $pharmacist) {
            $dispensing = PharmacyDispensing::create([
                'company_id' => $order->company_id,
                'branch_id' => $order->branch_id,
                'order_id' => $order->id,
                'prescription_id' => $order->prescription_id,
                'patient_id' => $order->patient_id,
                'store_id' => $store->id,
                'dispensing_number' => $this->numbers->generateDispensingNumber($order->company_id, $order->branch_id),
                'dispensed_by' => $pharmacist->id,
                'dispensed_at' => now(),
                'status' => PharmacyDispensing::STATUS_APPROVED,
            ]);

            foreach ($prepared as $line) {
                $this->fulfillLine($dispensing, $order, $store, $line, $pharmacist);
            }

            $order = $this->orderLifecycle->refreshFromItems($order);

            $dispensing->update([
                'status' => $order->status === 'fully_dispensed'
                    ? PharmacyDispensing::STATUS_FULLY_DISPENSED
                    : PharmacyDispensing::STATUS_PARTIALLY_DISPENSED,
            ]);

            $dispensing = $dispensing->refresh()->load('items');

            event(new DispensingCompleted($dispensing));

            return $dispensing;
        });
    }

    public function verify(PharmacyDispensing $dispensing, User $verifier): PharmacyDispensing
    {
        if ($dispensing->verified_at !== null) {
            throw ValidationException::withMessages(['dispensing' => 'This dispensing has already been verified.']);
        }

        $dispensing->update(['verified_by' => $verifier->id, 'verified_at' => now()]);

        return $dispensing->refresh();
    }

    /**
     * Resolves the order item/medication/substitution for one line and runs safety checks,
     * throwing before any stock is touched if unresolved alerts remain. Deliberately outside the
     * fulfillment transaction so alert rows persist even when the throw aborts dispensing.
     *
     * @return array{order_item: PharmacyOrderItem, medication: PharmacyMedication, quantity: int, is_substitution: bool, substitution_reason: ?string, batch_id: ?int}
     */
    private function prepareLine(PharmacyOrder $order, array $line): array
    {
        $orderItem = $order->items->firstWhere('id', $line['order_item_id']);

        if (! $orderItem instanceof PharmacyOrderItem) {
            throw ValidationException::withMessages(['lines' => "Order item {$line['order_item_id']} does not belong to this order."]);
        }

        $quantity = (int) $line['quantity'];

        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity to dispense must be at least 1.']);
        }

        $remaining = $orderItem->remainingQuantity();

        if ($remaining !== null && $quantity > $remaining) {
            throw ValidationException::withMessages(['quantity' => "Cannot dispense {$quantity}; only {$remaining} remain on this order item."]);
        }

        $medicationId = $line['medication_id'] ?? $orderItem->medication_id;

        if (! $medicationId) {
            throw ValidationException::withMessages(['medication_id' => 'This order item has no matched medication — resolve it before dispensing.']);
        }

        $medication = PharmacyMedication::query()->findOrFail($medicationId);
        $isSubstitution = $orderItem->medication_id !== null && $orderItem->medication_id !== $medication->id;

        if ($isSubstitution && $this->settings->get('pharmacy.substitution_requires_approval', true) && empty($line['substitution_approved_by'])) {
            throw ValidationException::withMessages(['substitution' => "Dispensing {$medication->name} as a substitute requires senior-pharmacist approval before stock is touched."]);
        }

        $alerts = $this->safety->runFor($orderItem, $order->patient, $medication);

        if ($alerts->isNotEmpty()) {
            throw ValidationException::withMessages([
                'safety_alert' => "Unacknowledged safety alert(s) for {$medication->name}: ".$alerts->pluck('explanation')->implode(' '),
            ]);
        }

        return [
            'order_item' => $orderItem,
            'medication' => $medication,
            'quantity' => $quantity,
            'is_substitution' => $isSubstitution,
            'substitution_reason' => $isSubstitution ? ($line['substitution_reason'] ?? null) : null,
            'batch_id' => $line['batch_id'] ?? null,
        ];
    }

    private function fulfillLine(PharmacyDispensing $dispensing, PharmacyOrder $order, PharmacyStore $store, array $prepared, User $pharmacist): void
    {
        $orderItem = $prepared['order_item'];
        $medication = $prepared['medication'];
        $quantity = $prepared['quantity'];
        $isSubstitution = $prepared['is_substitution'];

        $batch = $prepared['batch_id'] ? PharmacyBatch::query()->findOrFail($prepared['batch_id']) : null;

        $allocations = $batch
            ? collect([['stock' => null, 'quantity' => $quantity, 'batch' => $batch]])
            : $this->batchSelection->selectForDispense($store, $medication, $quantity)
                ->map(fn (array $allocation) => [...$allocation, 'batch' => $allocation['stock']->batch]);

        foreach ($allocations as $allocation) {
            $allocatedBatch = $allocation['batch'];
            $allocatedQuantity = $allocation['quantity'];

            $this->stock->debit($store, $medication, $allocatedBatch, $allocatedQuantity, PharmacyStockTransaction::TYPE_DISPENSING, $pharmacist, null, $dispensing);

            $dispensingItem = $dispensing->items()->create([
                'order_item_id' => $orderItem->id,
                'medication_id' => $medication->id,
                'batch_id' => $allocatedBatch->id,
                'quantity_prescribed' => $orderItem->quantity_prescribed,
                'quantity_dispensed' => $allocatedQuantity,
                'unit' => $medication->dispensing_unit,
                'substitution_flag' => $isSubstitution,
                'substitution_reason' => $prepared['substitution_reason'],
                'substituted_from_medication_id' => $isSubstitution ? $orderItem->medication_id : null,
            ]);

            if ($medication->is_controlled) {
                $this->controlledDrugs->record($store, $medication, $allocatedBatch, $allocatedQuantity, 'dispensing', 'out', $pharmacist, null, null, $dispensing);
            }

            $billingItem = BillingItem::query()
                ->forTenant($order->company_id, $order->branch_id)
                ->where('is_clinically_chargeable', true)
                ->where('clinical_event_type', 'pharmacy_medication')
                ->where('clinical_event_key', $medication->code)
                ->where('is_active', true)
                ->first();

            if ($billingItem) {
                $this->charges->createManual($billingItem, $allocatedQuantity, [
                    'company_id' => $order->company_id,
                    'branch_id' => $order->branch_id,
                    'patient_id' => $order->patient_id,
                    'encounter_id' => $order->encounter_id,
                ], $pharmacist);
            }

            event(new MedicationDispensed($dispensingItem));
        }

        $orderItem->update([
            'medication_id' => $orderItem->medication_id ?? $medication->id,
            'quantity_dispensed' => $orderItem->quantity_dispensed + $quantity,
            'status' => $orderItem->remainingQuantity() !== null && ($orderItem->quantity_prescribed - ($orderItem->quantity_dispensed + $quantity)) <= 0
                ? PharmacyOrderItem::STATUS_DISPENSED
                : PharmacyOrderItem::STATUS_PARTIALLY_DISPENSED,
        ]);
    }
}
