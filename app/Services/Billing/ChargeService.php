<?php

namespace App\Services\Billing;

use App\Events\Billing\ChargeCreated;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
    ) {}

    /**
     * @param  array{company_id:int,branch_id:int,patient_id:int,encounter_id?:int|null,department_id?:int|null,provider_id?:int|null,patient_category?:string|null,corporate_id?:int|null,insurance_policy_id?:int|null}  $context
     */
    public function createFromClinicalEvent(Model $sourceModel, string $eventType, string $eventKey, array $context): ?BillingCharge
    {
        $item = BillingItem::query()
            ->forTenant($context['company_id'], $context['branch_id'] ?? null)
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
                ->where('company_id', $context['company_id'])
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }

            return $this->create($item, $sourceModel, 1, $context, $idempotencyKey);
        });
    }

    /**
     * @param  array{company_id:int,branch_id:int,patient_id:int,encounter_id?:int|null,department_id?:int|null,provider_id?:int|null,patient_category?:string|null,corporate_id?:int|null,insurance_policy_id?:int|null}  $context
     */
    public function createManual(BillingItem $item, int $quantity, array $context, User $user): BillingCharge
    {
        return DB::transaction(function () use ($item, $quantity, $context, $user) {
            $idempotencyKey = hash('sha256', sprintf('manual:%d:%s', $user->id, (string) \Illuminate\Support\Str::uuid()));

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

    private function create(BillingItem $item, ?Model $source, int $quantity, array $context, string $idempotencyKey, ?User $user = null): BillingCharge
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
            'company_id' => $context['company_id'],
            'branch_id' => $context['branch_id'],
            'patient_id' => $context['patient_id'],
            'encounter_id' => $context['encounter_id'] ?? null,
            'billing_item_id' => $item->id,
            'department_id' => $context['department_id'] ?? null,
            'provider_id' => $context['provider_id'] ?? null,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
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
