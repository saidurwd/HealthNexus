<?php

namespace App\Services\Facility;

use App\Models\Facility\FacAmbulance;
use App\Models\Facility\FacAmbulanceTrip;
use App\Models\Facility\FacAsset;
use App\Models\Facility\FacFuelLog;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** The ambulance fleet and its trips. A vehicle on a trip, in maintenance or retired cannot be sent; a trip is requested, dispatched, arrives and completes, with the odometer and fuel kept. */
class AmbulanceService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly FacilityPolicy $policy, private readonly AssetService $assets, private readonly AuditLogger $audit) {}

    public function add(int $organizationId, string $registration, string $type, ?FacAsset $asset = null, int $odometer = 0): FacAmbulance
    {
        $registration = strtoupper(trim($registration));
        if ($registration === '' || ! in_array($type, $this->policy->list('ambulance.types'), true)) {
            throw ValidationException::withMessages(['registration_no' => __('Give the vehicle a registration number and a type.')]);
        }
        if (FacAmbulance::query()->where(['organization_id' => $organizationId, 'registration_no' => $registration])->exists()) {
            throw ValidationException::withMessages(['registration_no' => __('That vehicle is already on the list.')]);
        }

        return FacAmbulance::create(['organization_id' => $organizationId, 'registration_no' => $registration, 'type' => $type, 'asset_id' => $asset?->id, 'status' => 'available', 'odometer_km' => max(0, $odometer)]);
    }

    public function setMaintenance(FacAmbulance $a, bool $on): FacAmbulance
    {
        if ($a->status === 'on_trip') {
            throw ValidationException::withMessages(['status' => __('The vehicle is on a trip.')]);
        }
        $a->update(['status' => $on ? 'maintenance' : 'available']);

        return $a;
    }

    /** @param array<string,mixed> $d kind, from_place, to_place, patient_id, ed_visit_id, branch_id */
    public function request(int $organizationId, array $d, User $by): FacAmbulanceTrip
    {
        if (! in_array($d['kind'] ?? '', $this->policy->list('ambulance.trip_kinds'), true)) {
            throw ValidationException::withMessages(['kind' => __('Choose the kind of trip.')]);
        }
        if (mb_strlen(trim((string) ($d['from_place'] ?? ''))) < 2 || mb_strlen(trim((string) ($d['to_place'] ?? ''))) < 2) {
            throw ValidationException::withMessages(['from_place' => __('Say where from and where to.')]);
        }
        if (! empty($d['patient_id']) && ! DB::table('patients')->where('organization_id', $organizationId)->where('id', $d['patient_id'])->exists()) {
            throw ValidationException::withMessages(['patient_id' => __('Unknown patient.')]);
        }
        $trip = FacAmbulanceTrip::create(['organization_id' => $organizationId, 'branch_id' => $d['branch_id'] ?? null, 'number' => $this->numbers->next('fac.trip', $organizationId, null, prefix: 'TRP', format: '{PREFIX}-{YEAR}-{SEQ:5}', period: NumberSeriesService::PERIOD_YEAR),
            'kind' => $d['kind'], 'status' => 'requested', 'from_place' => mb_substr(trim($d['from_place']), 0, 150), 'to_place' => mb_substr(trim($d['to_place']), 0, 150), 'patient_id' => $d['patient_id'] ?? null, 'ed_visit_id' => $d['ed_visit_id'] ?? null,
            'requested_at' => now(), 'requested_by' => $by->id]);
        $this->audit->log('CREATE', FacAmbulanceTrip::class, $trip->id, null, ['number' => $trip->number, 'kind' => $trip->kind]);

        return $trip;
    }

    public function dispatch(FacAmbulanceTrip $trip, FacAmbulance $ambulance, string $driver): FacAmbulanceTrip
    {
        if ($trip->status !== 'requested') {
            throw ValidationException::withMessages(['status' => __('This trip has already been sent.')]);
        }
        if (mb_strlen(trim($driver)) < 2) {
            throw ValidationException::withMessages(['driver_name' => __('Name the driver.')]);
        }

        return DB::transaction(function () use ($trip, $ambulance, $driver) {
            $a = FacAmbulance::query()->lockForUpdate()->findOrFail($ambulance->id);
            if ($a->organization_id !== $trip->organization_id || $a->status !== 'available') {
                throw ValidationException::withMessages(['ambulance_id' => __('That vehicle cannot be sent: it is :s.', ['s' => str_replace('_', ' ', $a->status)])]);
            }
            if ($a->asset_id && ($asset = FacAsset::query()->find($a->asset_id)) && ! $this->assets->usable($asset)) {
                throw ValidationException::withMessages(['ambulance_id' => __('That vehicle cannot be sent: :r', ['r' => (string) $this->assets->reason($asset)])]);
            }
            $a->update(['status' => 'on_trip']);
            $trip->update(['ambulance_id' => $a->id, 'status' => 'dispatched', 'dispatched_at' => now(), 'driver_name' => mb_substr(trim($driver), 0, 100), 'odometer_start' => $a->odometer_km]);

            return $trip->refresh();
        });
    }

    public function arrive(FacAmbulanceTrip $trip): FacAmbulanceTrip
    {
        if ($trip->status !== 'dispatched') {
            throw ValidationException::withMessages(['status' => __('The trip has not been sent.')]);
        }
        $trip->update(['status' => 'arrived', 'arrived_at' => now()]);

        return $trip;
    }

    public function complete(FacAmbulanceTrip $trip, int $odometerEnd): FacAmbulanceTrip
    {
        if (! in_array($trip->status, ['dispatched', 'arrived'], true)) {
            throw ValidationException::withMessages(['status' => __('The trip is not under way.')]);
        }
        if ($odometerEnd < (int) $trip->odometer_start) {
            throw ValidationException::withMessages(['odometer_end' => __('The odometer cannot be less than at the start.')]);
        }

        return DB::transaction(function () use ($trip, $odometerEnd) {
            $trip->update(['status' => 'completed', 'completed_at' => now(), 'arrived_at' => $trip->arrived_at ?? now(), 'odometer_end' => $odometerEnd]);
            FacAmbulance::query()->whereKey($trip->ambulance_id)->update(['status' => 'available', 'odometer_km' => $odometerEnd]);

            return $trip->refresh();
        });
    }

    public function cancel(FacAmbulanceTrip $trip, string $reason): FacAmbulanceTrip
    {
        if (! in_array($trip->status, ['requested', 'dispatched'], true) || mb_strlen(trim($reason)) < 3) {
            throw ValidationException::withMessages(['reason' => __('Say why the trip is cancelled.')]);
        }
        DB::transaction(function () use ($trip, $reason) {
            $trip->ambulance_id && FacAmbulance::query()->whereKey($trip->ambulance_id)->where('status', 'on_trip')->update(['status' => 'available']);
            $trip->update(['status' => 'cancelled', 'cancel_reason' => mb_substr($reason, 0, 300)]);
        });

        return $trip;
    }

    public function fuel(FacAmbulance $a, float $litres, float $cost, ?int $odometer = null, ?string $date = null): FacFuelLog
    {
        if ($litres <= 0 || $cost < 0) {
            throw ValidationException::withMessages(['litres' => __('Say how much fuel and what it cost.')]);
        }

        return FacFuelLog::create(['ambulance_id' => $a->id, 'filled_on' => $date ?: today()->toDateString(), 'litres' => $litres, 'cost' => $cost, 'odometer_km' => $odometer]);
    }

    /** Minutes from the request to the vehicle leaving, for trips of a period. */
    public function averageResponseMinutes(int $organizationId, string $from, string $to): ?float
    {
        $trips = FacAmbulanceTrip::query()->where('organization_id', $organizationId)->whereNotNull('dispatched_at')->whereBetween('requested_at', [$from.' 00:00:00', $to.' 23:59:59'])->get(['requested_at', 'dispatched_at']);

        return $trips->isEmpty() ? null : round($trips->avg(fn ($t) => $t->requested_at->diffInMinutes($t->dispatched_at)), 1);
    }
}
