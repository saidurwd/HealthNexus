<?php

namespace App\Services\Radiology;

use App\Events\Radiology\RadiologyOrderRegistered;
use App\Models\ClinicalOrder;
use App\Models\Encounter;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyProcedure;
use App\Services\Billing\ChargeService;
use Illuminate\Support\Facades\DB;

/**
 * Converts a Phase 3 ClinicalOrder (order_type = 'radiology') into a RadiologyOrder +
 * RadiologyOrderItems. Each ClinicalOrderItem's free-text item_name/item_code is best-effort
 * matched against the radiology_procedures catalog by code first, then exact name — an
 * unmatched item is still recorded (procedure_id left null, status 'unmatched') rather than
 * silently dropped, mirroring LabRegistrationService.
 *
 * Billing is per-procedure, not per-order (Phase 6 plan decision #8): for every item that
 * resolves to a real RadiologyProcedure, ChargeService::createFromClinicalEvent() is called
 * once, keyed on the procedure's own code — never the generic 'clinical_order'/'radiology'
 * charge path.
 */
class RadiologyRegistrationService
{
    public function __construct(
        private readonly RadiologyNumberGenerator $numbers,
        private readonly RadiologyOrderLifecycleService $lifecycle,
        private readonly ChargeService $charges,
    ) {}

    public function registerFromClinicalOrder(Encounter $encounter, ClinicalOrder $clinicalOrder): RadiologyOrder
    {
        return DB::transaction(function () use ($encounter, $clinicalOrder) {
            $order = RadiologyOrder::create([
                'company_id' => $clinicalOrder->company_id,
                'branch_id' => $clinicalOrder->branch_id,
                'patient_id' => $clinicalOrder->patient_id,
                'encounter_id' => $encounter->id,
                'clinical_order_id' => $clinicalOrder->id,
                'order_number' => $this->numbers->generateOrderNumber($clinicalOrder->company_id, $clinicalOrder->branch_id),
                'priority' => $clinicalOrder->priority ?? 'routine',
                'status' => 'ordered',
                'ordered_by' => $clinicalOrder->ordered_by,
                'ordered_at' => $clinicalOrder->ordered_at ?? now(),
                'clinical_indication' => $clinicalOrder->notes,
            ]);

            foreach ($clinicalOrder->items as $clinicalItem) {
                $procedure = $this->matchProcedure($clinicalOrder->company_id, $clinicalItem->item_code, $clinicalItem->item_name);

                $order->items()->create([
                    'procedure_id' => $procedure?->id,
                    'requested_procedure_name' => $clinicalItem->item_name,
                    'priority' => $order->priority,
                    'status' => $procedure ? 'pending' : 'unmatched',
                    'result_status' => 'pending',
                    'requested_at' => now(),
                ]);

                if ($procedure) {
                    $this->charges->createFromClinicalEvent($clinicalItem, 'radiology_procedure', $procedure->code, [
                        'company_id' => $clinicalOrder->company_id,
                        'branch_id' => $clinicalOrder->branch_id,
                        'patient_id' => $clinicalOrder->patient_id,
                        'encounter_id' => $encounter->id,
                        'department_id' => $encounter->department_id,
                        'provider_id' => $clinicalOrder->provider_id,
                    ]);
                }
            }

            $accessionNumber = $this->numbers->generateAccessionNumber($clinicalOrder->company_id, $clinicalOrder->branch_id);

            $order->update([
                'accession_number' => $accessionNumber,
                'registered_by' => $clinicalOrder->ordered_by,
                'registered_at' => now(),
            ]);

            $order = $this->lifecycle->transitionTo($order->refresh(), 'registered');

            event(new RadiologyOrderRegistered($order));

            return $order;
        });
    }

    private function matchProcedure(int $companyId, ?string $itemCode, string $itemName): ?RadiologyProcedure
    {
        $query = RadiologyProcedure::query()->forTenant($companyId)->where('is_active', true);

        if ($itemCode) {
            $byCode = (clone $query)->where('code', $itemCode)->first();

            if ($byCode) {
                return $byCode;
            }
        }

        return (clone $query)->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($itemName))])->first();
    }
}
