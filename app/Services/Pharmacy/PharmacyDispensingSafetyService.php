<?php

namespace App\Services\Pharmacy;

use App\Models\Patient;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyOrderItem;
use App\Models\Pharmacy\PharmacySafetyAlert;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Runs PharmacySafetyCheckService for an order item about to be dispensed and persists the
 * resulting alerts, deduplicated against any still-unresolved alert already raised for that item.
 * PharmacyDispensingService must never deduct stock for a line with an unacknowledged
 * (non-overridden) alert — see hasBlockingAlerts().
 */
class PharmacyDispensingSafetyService
{
    public function __construct(private readonly PharmacySafetyCheckService $safetyChecks) {}

    /**
     * @return Collection<int, PharmacySafetyAlert>
     */
    public function runFor(PharmacyOrderItem $orderItem, Patient $patient, PharmacyMedication $medication, Collection $activeMedications = new Collection): Collection
    {
        return DB::transaction(function () use ($orderItem, $patient, $medication, $activeMedications) {
            // Always queried fresh rather than via $orderItem->safetyAlerts: callers may reuse the
            // same $orderItem instance across multiple dispense attempts, and Eloquent's relation
            // caching would otherwise return a stale (pre-creation/pre-override) collection.
            $existing = PharmacySafetyAlert::query()->where('order_item_id', $orderItem->id)->get();

            $created = collect();

            foreach ($this->safetyChecks->check($patient, $medication, $activeMedications) as $alertData) {
                // Already surfaced for this item before — whether still pending or already
                // resolved via override — so it is never re-raised as a fresh, unresolved alert.
                $alreadySurfaced = $existing->contains(
                    fn (PharmacySafetyAlert $alert) => $alert->alert_type === $alertData['alert_type']
                        && $alert->interacting_reference === $alertData['interacting_reference']
                );

                if ($alreadySurfaced) {
                    continue;
                }

                $created->push(PharmacySafetyAlert::create([
                    'company_id' => $medication->company_id,
                    'branch_id' => $medication->branch_id,
                    'order_item_id' => $orderItem->id,
                    'medication_id' => $medication->id,
                    ...$alertData,
                ]));
            }

            return $existing->where('is_overridden', false)->merge($created)->values();
        });
    }

    public function hasBlockingAlerts(PharmacyOrderItem $orderItem): bool
    {
        return PharmacySafetyAlert::query()
            ->where('order_item_id', $orderItem->id)
            ->where('is_overridden', false)
            ->exists();
    }

    public function override(PharmacySafetyAlert $alert, string $reason, User $user): PharmacySafetyAlert
    {
        $alert->update([
            'is_overridden' => true,
            'override_reason' => $reason,
            'overridden_by' => $user->id,
            'overridden_at' => now(),
        ]);

        return $alert->refresh();
    }
}
