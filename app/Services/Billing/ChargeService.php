<?php

namespace App\Services\Billing;

use App\Events\Billing\ChargeCreated;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingItem;
use App\Models\User;
use App\Services\Billing\Events\ChargeEventLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Turns a billable event into a BillingCharge. Never charges blindly: a clinical event only
 * produces a charge when an explicit BillingItem mapping exists for it
 * (is_clinically_chargeable + clinical_event_type + clinical_event_key).
 */
class ChargeService
{
    public function __construct(
        private readonly BillingPricingService $pricing,
        private readonly BillingCalculationService $calculator,
        private readonly ChargeEventLogger $eventLog,
    ) {}

    /**
     * @param  array{organization_id:int,branch_id:int,patient_id:int,encounter_id?:int|null,department_id?:int|null,provider_id?:int|null,patient_category?:string|null,corporate_id?:int|null,insurance_policy_id?:int|null}  $context
     */
    public function createFromClinicalEvent(Model $sourceModel, string $eventType, string $eventKey, array $context): ?BillingCharge
    {
        $item = BillingItem::query()
            ->forTenant($context['organization_id'], $context['branch_id'] ?? null)
            ->where('is_clinically_chargeable', true)
            ->where('clinical_event_type', $eventType)
            ->where('clinical_event_key', $eventKey)
            ->where('is_active', true)
            ->first();

        if (! $item) {
            return null;
        }

        $idempotencyKey = hash('sha256', sprintf('%s:%s:%d', $sourceModel->getMorphClass(), $sourceModel->getKey(), $item->id));

        return DB::transaction(function () use ($sourceModel, $item, $context, $idempotencyKey) {
            $existing = BillingCharge::query()
                ->where('organization_id', $context['organization_id'])
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            $charge = $existing ?? $this->create($item, $sourceModel, 1, $context, $idempotencyKey);

            // Adapter for modules that still call Billing directly: the Charge Event Contract log sees every charge (H-05).
            $this->eventLog->recordAdapted($charge, $item, $sourceModel, $existing !== null);

            return $charge;
        });
    }

    /** Entry point for the Charge Event Publisher; the key is the contract key, so a repeat never double-charges. */
    public function createForEvent(BillingItem $item, int $quantity, array $context, string $idempotencyKey, ?string $sourceType, string|int|null $sourceId, ?int $userId): BillingCharge
    {
        return DB::transaction(function () use ($item, $quantity, $context, $idempotencyKey, $sourceType, $sourceId, $userId) {
            $existing = BillingCharge::query()
                ->where('organization_id', $context['organization_id'])->where('idempotency_key', $idempotencyKey)->first();

            return $existing ?? $this->create($item, null, $quantity, $context, $idempotencyKey, $userId ? User::find($userId) : null, $sourceType, $sourceId);
        });
    }

    /**
     * @param  array{organization_id:int,branch_id:int,patient_id:int,encounter_id?:int|null,department_id?:int|null,provider_id?:int|null,patient_category?:string|null,corporate_id?:int|null,insurance_policy_id?:int|null}  $context
     */
    public function createManual(BillingItem $item, int $quantity, array $context, User $user): BillingCharge
    {
        return DB::transaction(function () use ($item, $quantity, $context, $user) {
            $idempotencyKey = hash('sha256', sprintf('manual:%d:%s', $user->id, (string) Str::uuid()));

            return $this->create($item, null, $quantity, $context, $idempotencyKey, $user);
        });
    }

    public function cancel(BillingCharge $charge, string $reason, User $user): BillingCharge
    {
        return DB::transaction(function () use ($charge, $reason, $user) {
            if ($charge->isBilled()) {
                throw ValidationException::withMessages(['charge' => 'A charge that has already been added to an invoice cannot be cancelled directly — cancel or adjust the invoice instead.']);
            }

            $charge->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
                'cancellation_reason' => $reason,
            ]);

            return $charge->refresh();
        });
    }

    private function create(BillingItem $item, ?Model $source, int $quantity, array $context, string $idempotencyKey, ?User $user = null, ?string $sourceType = null, string|int|null $sourceId = null): BillingCharge
    {
        $price = $this->pricing->resolvePrice($item, $context);

        $taxRate = $item->is_taxable && $item->taxCategory ? (string) $item->taxCategory->rate : '0';
        $taxInclusive = $item->is_taxable && $item->taxCategory ? (bool) $item->taxCategory->is_inclusive : $price['tax_included'];

        $totals = $this->calculator->calculateLine(
            $price['unit_price'],
            $quantity,
            $price['discount_type'],
            $price['discount_value'],
            $taxRate,
            $taxInclusive,
        );

        $charge = BillingCharge::create([
            'organization_id' => $context['organization_id'],
            'branch_id' => $context['branch_id'],
            'patient_id' => $context['patient_id'],
            'encounter_id' => $context['encounter_id'] ?? null,
            'billing_item_id' => $item->id,
            'department_id' => $context['department_id'] ?? null,
            'provider_id' => $context['provider_id'] ?? null,
            'source_type' => $source?->getMorphClass() ?? $sourceType,
            'source_id' => $source?->getKey() ?? $sourceId,
            'idempotency_key' => $idempotencyKey,
            'status' => 'pending',
            'quantity' => $quantity,
            'currency' => $context['currency'] ?? 'BDT',
            'unit_price' => $price['unit_price'],
            'gross_amount' => $totals['gross_amount'],
            'discount_type' => $price['discount_type'],
            'discount_amount' => $totals['discount_amount'],
            'tax_amount' => $totals['tax_amount'],
            'net_amount' => $totals['net_amount'],
            'charged_at' => now(),
            'created_by' => $user?->id,
        ]);

        event(new ChargeCreated($charge));

        return $charge;
    }
}
