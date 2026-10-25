<?php

namespace App\Services\Billing\Events;

use App\Contracts\Billing\ChargeEventPublisher;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingItem;
use App\Models\Billing\ChargeEventLog;
use App\Models\User;
use App\Services\Billing\ChargeService;
use Illuminate\Support\Facades\DB;

class BillingChargeEventPublisher implements ChargeEventPublisher
{
    public function __construct(private readonly ChargeService $charges) {}

    public function publish(ChargeEventData $event): ChargeEventLog
    {
        return DB::transaction(function () use ($event) {
            $log = ChargeEventLog::query()
                ->where('organization_id', $event->organizationId)->where('idempotency_key', $event->idempotencyKey)
                ->lockForUpdate()->first();

            if ($log && $log->outcome !== ChargeEventLog::OUTCOME_REJECTED) {
                $log->increment('publish_count');

                return $log->refresh();
            }

            $log ??= $this->newLog($event);

            if (! $event->isChargeable) {
                return $this->finish($log, ChargeEventLog::OUTCOME_NOT_CHARGEABLE, $event->noChargeReason ?? 'No-charge event');
            }

            if (! is_numeric($event->quantity) || (float) $event->quantity <= 0 || floor((float) $event->quantity) != (float) $event->quantity) {
                return $this->finish($log, ChargeEventLog::OUTCOME_REJECTED, 'Quantity must be a whole number greater than zero.');
            }

            $item = BillingItem::query()
                ->forTenant($event->organizationId, $event->branchId)
                ->where('item_code', $event->serviceCode)->where('is_active', true)->first();

            if (! $item) {
                return $this->finish($log, ChargeEventLog::OUTCOME_REJECTED, "No active billing item with code [{$event->serviceCode}].");
            }

            $charge = $this->charges->createForEvent($item, (int) $event->quantity, [
                'organization_id' => $event->organizationId, 'branch_id' => $event->branchId, 'patient_id' => $event->patientId,
                'encounter_id' => $event->encounterId, 'department_id' => $event->departmentId, 'provider_id' => $event->providerId,
            ], $event->idempotencyKey, $event->sourceType, $event->sourceId, $event->performedBy);

            $log->billing_charge_id = $charge->id;

            return $this->finish($log, ChargeEventLog::OUTCOME_ACCEPTED, null);
        });
    }

    public function reverse(int $organizationId, string $idempotencyKey, string $reason, ?int $userId = null): ChargeEventLog
    {
        return DB::transaction(function () use ($organizationId, $idempotencyKey, $reason, $userId) {
            $log = ChargeEventLog::query()->where('organization_id', $organizationId)->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()->firstOrFail();

            if ($log->outcome !== ChargeEventLog::OUTCOME_ACCEPTED || ! $log->billing_charge_id) {
                return $log; // nothing was charged, or it is already reversed
            }

            $charge = BillingCharge::query()->lockForUpdate()->findOrFail($log->billing_charge_id);

            if ($charge->isBilled()) {
                // An invoiced charge is corrected by credit note / adjustment with approval, not silently cancelled.
                $log->update(['outcome' => ChargeEventLog::OUTCOME_REVERSAL_NEEDS_CREDIT_NOTE, 'outcome_message' => $reason]);

                return $log->refresh();
            }

            $this->charges->cancel($charge, $reason, $userId ? User::findOrFail($userId) : $this->systemUser());
            $log->update(['outcome' => ChargeEventLog::OUTCOME_REVERSED, 'outcome_message' => $reason]);

            return $log->refresh();
        });
    }

    private function newLog(ChargeEventData $event): ChargeEventLog
    {
        return new ChargeEventLog([
            'organization_id' => $event->organizationId, 'hospital_id' => $event->hospitalId, 'branch_id' => $event->branchId,
            'idempotency_key' => $event->idempotencyKey, 'module' => strtoupper($event->module),
            'visit_ref' => (string) ($event->visitRef ?? $event->encounterId ?? $event->admissionId ?? ''),
            'service_code' => $event->serviceCode, 'patient_id' => $event->patientId, 'encounter_id' => $event->encounterId,
            'admission_id' => $event->admissionId, 'quantity' => $event->quantity, 'is_chargeable' => $event->isChargeable,
            'no_charge_reason' => $event->noChargeReason, 'billing_party_hint' => $event->billingPartyHint,
            'source_type' => $event->sourceType, 'source_id' => $event->sourceId !== null ? (string) $event->sourceId : null,
            'performed_at' => $event->performedAt ?? now(), 'performed_by' => $event->performedBy, 'outcome' => 'pending',
        ]);
    }

    private function finish(ChargeEventLog $log, string $outcome, ?string $message): ChargeEventLog
    {
        $log->fill(['outcome' => $outcome, 'outcome_message' => $message])->save();

        return $log->refresh();
    }

    private function systemUser(): User
    {
        return new User;
    }
}
