<?php

namespace App\Services\Ot;

use App\Models\Billing\BillingCharge;
use App\Models\Ot\OtImplant;
use App\Models\Ot\OtSurgery;
use App\Models\User;
use App\Services\Billing\ChargeService;

/**
 * OT never creates its own invoice/payment records (spec §54) — every charge flows through the
 * existing Billing ChargeService. One-time surgery fees are naturally idempotent (keyed on the
 * surgery itself, each resolving to a different BillingItem). Implant charges are keyed on the
 * implant row itself, since the same billable item could repeat within one surgery (decision #9).
 */
class OtChargeService
{
    public function __construct(private readonly ChargeService $charges) {}

    public function chargeProcedureFee(OtSurgery $surgery, User $user): ?BillingCharge
    {
        $surgery->loadMissing('primaryProcedure.category');

        return $this->charges->createFromClinicalEvent($surgery, 'ot_procedure_fee', $surgery->primaryProcedure?->category?->code ?? 'general', $this->context($surgery));
    }

    public function chargeTheatreUsageFee(OtSurgery $surgery, User $user): ?BillingCharge
    {
        return $this->charges->createFromClinicalEvent($surgery, 'ot_theatre_usage', 'general', $this->context($surgery));
    }

    public function chargeAnesthesiaFee(OtSurgery $surgery, User $user): ?BillingCharge
    {
        return $this->charges->createFromClinicalEvent($surgery, 'ot_anesthesia_fee', 'general', $this->context($surgery));
    }

    public function chargeSurgeonFee(OtSurgery $surgery, User $user): ?BillingCharge
    {
        return $this->charges->createFromClinicalEvent($surgery, 'ot_surgeon_fee', 'general', $this->context($surgery));
    }

    public function chargeImplant(OtImplant $implant, User $user): ?BillingCharge
    {
        $surgery = $implant->surgery;

        return $this->charges->createFromClinicalEvent($implant, 'ot_implant', 'general', $this->context($surgery));
    }

    private function context(OtSurgery $surgery): array
    {
        return [
            'organization_id' => $surgery->organization_id,
            'branch_id' => $surgery->branch_id,
            'patient_id' => $surgery->patient_id,
            'encounter_id' => $surgery->encounter_id,
        ];
    }
}
