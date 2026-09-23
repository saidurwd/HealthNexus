<?php

namespace App\Services\Laboratory;

use App\Events\Laboratory\LabOrderRegistered;
use App\Models\ClinicalOrder;
use App\Models\Encounter;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabTest;
use App\Services\Billing\ChargeService;
use Illuminate\Support\Facades\DB;

/**
 * Converts a Phase 3 ClinicalOrder (order_type = 'laboratory') into a LabOrder + LabOrderItems.
 * Each ClinicalOrderItem's free-text item_name/item_code is best-effort matched against the
 * lab_tests catalog by code first, then exact name — an unmatched item is still recorded
 * (test_id left null, status 'unmatched') rather than silently dropped, so lab staff can
 * resolve it manually instead of losing the request.
 *
 * Billing is per-test, not per-order (see Phase 5 plan decision #2): for every item that
 * resolves to a real LabTest, ChargeService::createFromClinicalEvent() is called once, keyed
 * on the test's own code — never the generic 'clinical_order'/'laboratory' charge path.
 */
class LabRegistrationService
{
    public function __construct(
        private readonly LabNumberGenerator $numbers,
        private readonly LabOrderLifecycleService $lifecycle,
        private readonly ChargeService $charges,
    ) {}

    public function registerFromClinicalOrder(Encounter $encounter, ClinicalOrder $clinicalOrder): LabOrder
    {
        return DB::transaction(function () use ($encounter, $clinicalOrder) {
            $order = LabOrder::create([
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
                'clinical_notes' => $clinicalOrder->notes,
            ]);

            foreach ($clinicalOrder->items as $clinicalItem) {
                $test = $this->matchTest($clinicalOrder->company_id, $clinicalItem->item_code, $clinicalItem->item_name);

                $order->items()->create([
                    'test_id' => $test?->id,
                    'requested_test_name' => $clinicalItem->item_name,
                    'priority' => $order->priority,
                    'status' => $test ? 'pending' : 'unmatched',
                    'result_status' => 'pending',
                    'requested_at' => now(),
                ]);

                if ($test) {
                    $this->charges->createFromClinicalEvent($clinicalItem, 'lab_test', $test->code, [
                        'company_id' => $clinicalOrder->company_id,
                        'branch_id' => $clinicalOrder->branch_id,
                        'patient_id' => $clinicalOrder->patient_id,
                        'encounter_id' => $encounter->id,
                        'department_id' => $encounter->department_id,
                        'provider_id' => $clinicalOrder->provider_id,
                    ]);
                }
            }

            $order = $this->lifecycle->transitionTo($order, 'registered');

            event(new LabOrderRegistered($order));

            return $order;
        });
    }

    private function matchTest(int $companyId, ?string $itemCode, string $itemName): ?LabTest
    {
        $query = LabTest::query()->forTenant($companyId)->where('is_active', true);

        if ($itemCode) {
            $byCode = (clone $query)->where('code', $itemCode)->first();

            if ($byCode) {
                return $byCode;
            }
        }

        return (clone $query)->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($itemName))])->first();
    }
}
