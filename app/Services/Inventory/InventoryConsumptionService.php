<?php

namespace App\Services\Inventory;

use App\Contracts\Billing\ChargeEventPublisher;
use App\Models\Inventory\InventoryConsumption;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStore;
use App\Models\User;
use App\Services\Billing\Events\ChargeEventData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Use of supplies at the point of care (S12). Stock leaves the ward or OT store; when the use is for a patient and the item is chargeable, a ChargeEvent
 * goes to Billing — keyed so it can never charge twice. Inventory never knows a price.
 */
class InventoryConsumptionService
{
    public function __construct(private readonly InventoryStockService $stock, private readonly ChargeEventPublisher $charges) {}

    /**
     * @param  array{department_id?: ?int, patient_id?: ?int, encounter_id?: ?int, admission_id?: ?int, source_module?: string, branch_id?: ?int, hospital_id?: ?int, visit_ref?: string|int|null}  $context
     * @return list<InventoryConsumption>
     */
    public function record(User $by, InventoryStore $store, InventoryItem $item, float|int|string $quantity, array $context = []): array
    {
        $quantity = bcadd((string) $quantity, '0', 4);
        if (bccomp($quantity, '0', 4) <= 0) {
            throw ValidationException::withMessages(['quantity' => 'The quantity must be above zero.']);
        }
        if ($item->organization_id !== $store->organization_id) {
            throw ValidationException::withMessages(['item' => 'That item belongs to another organization.']);
        }
        $patientId = $context['patient_id'] ?? null;
        $chargeable = $patientId && $item->charge_service_code;
        $branchId = $context['branch_id'] ?? $store->branch_id;
        if ($chargeable && bccomp($quantity, bcadd((string) (int) $quantity, '0', 4), 4) !== 0) {
            // Billing charges in whole units; a part-unit would be rounded silently.
            throw ValidationException::withMessages(['quantity' => 'A charged item is recorded in whole units.']);
        }
        if ($chargeable && ! $branchId) {
            throw ValidationException::withMessages(['branch' => 'A charge needs the branch the patient was treated in.']);
        }

        return DB::transaction(function () use ($by, $store, $item, $quantity, $context, $patientId, $chargeable, $branchId) {
            $rows = [];
            foreach ($this->stock->allocate($store, $item, $quantity) as $pick) {
                $row = InventoryConsumption::create([
                    'organization_id' => $store->organization_id, 'store_id' => $store->id, 'item_id' => $item->id, 'batch_id' => $pick['batch_id'], 'department_id' => $context['department_id'] ?? $store->department_id,
                    'patient_id' => $patientId, 'encounter_id' => $context['encounter_id'] ?? null, 'admission_id' => $context['admission_id'] ?? null, 'source_module' => $context['source_module'] ?? 'inventory',
                    'quantity' => $pick['quantity'], 'unit_cost' => $pick['unit_cost'], 'consumed_by' => $by->id, 'consumed_at' => now(),
                ]);
                $this->stock->take($store, $item, $pick['batch_id'], $pick['quantity'], 'issue', "CONS:{$row->id}", $pick['unit_cost'], InventoryConsumption::class, $row->id, $by, 'Used at the point of care');
                $rows[] = $row;
            }
            if ($chargeable && $rows !== []) {
                $event = new ChargeEventData(
                    organizationId: $store->organization_id, branchId: (int) $branchId, patientId: (int) $patientId, module: 'INV', serviceCode: (string) $item->charge_service_code, sourceEventId: $rows[0]->id,
                    visitRef: $context['visit_ref'] ?? $context['encounter_id'] ?? $context['admission_id'] ?? null, hospitalId: $context['hospital_id'] ?? null, encounterId: $context['encounter_id'] ?? null,
                    admissionId: $context['admission_id'] ?? null, quantity: $quantity, performedAt: now(), performedBy: $by->id, departmentId: $context['department_id'] ?? $store->department_id,
                    sourceType: InventoryConsumption::class, sourceId: $rows[0]->id,
                );
                $this->charges->publish($event);
                foreach ($rows as $row) {
                    $row->update(['charge_key' => $event->idempotencyKey]);
                }
            }

            return $rows;
        });
    }
}
