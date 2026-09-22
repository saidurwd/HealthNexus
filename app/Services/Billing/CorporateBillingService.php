<?php

namespace App\Services\Billing;

use App\Models\Billing\BillingCorporate;
use App\Models\Billing\BillingCorporateContract;
use App\Models\Patient;
use Illuminate\Support\Carbon;

/**
 * Corporate billing foundation: contract resolution and membership checks feeding
 * BillingPricingService, plus the lookups the Outstanding/Receivables reports need.
 * Deliberately no claims/receivables workflow engine — that is out of Phase 4 scope.
 */
class CorporateBillingService
{
    public function resolveContract(BillingCorporate $corporate, ?string $date = null): ?BillingCorporateContract
    {
        $date ??= Carbon::now()->toDateString();

        return $corporate->contracts()
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date))
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->first();
    }

    public function isMember(Patient $patient, BillingCorporate $corporate): bool
    {
        return $corporate->members()
            ->where('patient_id', $patient->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * @return array{discount_type:string,discount_value:string}
     */
    public function contractDiscount(BillingCorporateContract $contract): array
    {
        return [
            'discount_type' => $contract->discount_type ?? 'none',
            'discount_value' => (string) ($contract->discount_value ?? '0'),
        ];
    }
}
