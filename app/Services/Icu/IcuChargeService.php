<?php

namespace App\Services\Icu;

use App\Models\Billing\BillingCharge;
use App\Models\Icu\IcuChargeEvent;
use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuProcedureRecord;
use App\Models\User;
use App\Services\Billing\ChargeService;
use Illuminate\Support\Facades\DB;

/**
 * ICU never implements its own invoice/payment engine (spec §39) — thin wrapper over the
 * existing Billing ChargeService, mirroring OtChargeService/IpdChargeService exactly. Recurring
 * bed-day/ventilator-day charges key off icu_charge_events (the per-day idempotency anchor,
 * decision #11), never off icu_episodes itself. One-time charges (procedures, critical-care
 * service fee) key directly off their own distinct BillingItem, collision-free.
 */
class IcuChargeService
{
    public function __construct(private readonly ChargeService $charges) {}

    public function chargeBedDay(IcuEpisode $episode, string $chargeDate, User $user): ?BillingCharge
    {
        return DB::transaction(function () use ($episode, $chargeDate) {
            $event = IcuChargeEvent::query()->firstOrCreate([
                'episode_id' => $episode->id,
                'charge_type' => 'bed_day',
                'charge_date' => $chargeDate,
            ]);

            return $this->charges->createFromClinicalEvent($event, 'icu_bed_day', 'general', [
                'organization_id' => $episode->organization_id,
                'branch_id' => $episode->branch_id,
                'patient_id' => $episode->patient_id,
                'encounter_id' => $episode->encounter_id,
            ]);
        });
    }

    public function chargeVentilatorDay(IcuEpisode $episode, string $chargeDate, User $user): ?BillingCharge
    {
        return DB::transaction(function () use ($episode, $chargeDate) {
            $event = IcuChargeEvent::query()->firstOrCreate([
                'episode_id' => $episode->id,
                'charge_type' => 'ventilator_day',
                'charge_date' => $chargeDate,
            ]);

            return $this->charges->createFromClinicalEvent($event, 'icu_ventilator_day', 'general', [
                'organization_id' => $episode->organization_id,
                'branch_id' => $episode->branch_id,
                'patient_id' => $episode->patient_id,
                'encounter_id' => $episode->encounter_id,
            ]);
        });
    }

    public function chargeCriticalCareService(IcuEpisode $episode, string $chargeDate, User $user): ?BillingCharge
    {
        return DB::transaction(function () use ($episode, $chargeDate) {
            $event = IcuChargeEvent::query()->firstOrCreate([
                'episode_id' => $episode->id,
                'charge_type' => 'critical_care_service',
                'charge_date' => $chargeDate,
            ]);

            return $this->charges->createFromClinicalEvent($event, 'icu_critical_care_service', 'general', [
                'organization_id' => $episode->organization_id,
                'branch_id' => $episode->branch_id,
                'patient_id' => $episode->patient_id,
                'encounter_id' => $episode->encounter_id,
            ]);
        });
    }

    public function chargeProcedure(IcuProcedureRecord $record, User $user): ?BillingCharge
    {
        $record->loadMissing(['procedure', 'episode']);

        return $this->charges->createFromClinicalEvent($record, 'icu_procedure_fee', $record->procedure?->code ?? 'general', [
            'organization_id' => $record->organization_id,
            'branch_id' => $record->branch_id,
            'patient_id' => $record->episode->patient_id,
            'encounter_id' => $record->episode->encounter_id,
        ]);
    }
}
