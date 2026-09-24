<?php

namespace App\Services\Ipd;

use App\Models\Billing\BillingCharge;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedChargeEvent;
use App\Models\User;
use App\Services\Billing\ChargeService;
use Illuminate\Support\Facades\DB;

/**
 * IPD never creates its own invoice/payment records (spec §28) — every charge flows through the
 * existing Billing ChargeService. The one-time admission fee is naturally idempotent (keyed on
 * the admission model itself). Daily bed charges need a per-day idempotency anchor that doesn't
 * exist in Billing yet, so ipd_bed_charge_events supplies one distinct source model per calendar
 * day — an idempotency anchor, not a parallel invoice engine.
 */
class IpdChargeService
{
    public function __construct(private readonly ChargeService $charges) {}

    public function chargeAdmissionFee(IpdAdmission $admission, User $user): ?BillingCharge
    {
        $admission->loadMissing('admissionType');

        return $this->charges->createFromClinicalEvent($admission, 'ipd_admission_fee', $admission->admissionType?->code ?? 'general', [
            'company_id' => $admission->company_id,
            'branch_id' => $admission->branch_id,
            'patient_id' => $admission->patient_id,
            'encounter_id' => $admission->encounter_id,
            'department_id' => $admission->department_id,
        ]);
    }

    public function chargeBedDay(IpdAdmission $admission, IpdBed $bed, string $chargeDate, User $user): ?BillingCharge
    {
        return DB::transaction(function () use ($admission, $bed, $chargeDate) {
            $bed->loadMissing('bedType');

            $event = IpdBedChargeEvent::query()->firstOrCreate([
                'admission_id' => $admission->id,
                'charge_date' => $chargeDate,
            ], [
                'bed_id' => $bed->id,
            ]);

            return $this->charges->createFromClinicalEvent($event, 'ipd_bed_day', $bed->bedType?->code ?? 'general', [
                'company_id' => $admission->company_id,
                'branch_id' => $admission->branch_id,
                'patient_id' => $admission->patient_id,
                'encounter_id' => $admission->encounter_id,
                'department_id' => $admission->department_id,
            ]);
        });
    }
}
