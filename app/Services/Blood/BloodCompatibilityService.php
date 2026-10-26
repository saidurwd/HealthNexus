<?php

namespace App\Services\Blood;

use App\Contracts\Billing\ChargeEventPublisher;
use App\Models\Blood\BloodCrossmatch;
use App\Models\Blood\BloodRequest;
use App\Models\Blood\BloodReservation;
use App\Models\Blood\BloodSample;
use App\Models\Blood\BloodUnit;
use App\Models\Patient;
use App\Models\User;
use App\Services\Billing\Events\ChargeEventData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Compatibility testing and reservation. An incompatible unit is recorded as incompatible and can never be reserved; a reservation lapses and frees the unit. */
class BloodCompatibilityService
{
    public function __construct(
        private readonly BloodPolicy $policy,
        private readonly BloodUnitService $units,
        private readonly BloodRequestService $requests,
        private readonly ChargeEventPublisher $charges,
    ) {}

    /** @return string|null why the unit may NOT go to this patient, or null when it may */
    public function incompatibility(BloodRequest $request, BloodUnit $unit, array $group): ?string
    {
        if ($unit->component !== $request->component) {
            return 'The unit is not the component that was asked for.';
        }
        if (! $unit->abo || ! $unit->rh) {
            return 'The unit has no confirmed group.';
        }
        $kind = $unit->kind();
        if (! in_array($unit->abo, $this->policy->acceptableDonorGroups($group['abo'], $kind === 'red' ? 'red' : 'plasma'), true)) {
            return "ABO: a {$unit->abo} unit cannot go to a {$group['abo']} patient.";
        }
        if ($kind === 'red' && $group['rh'] === 'negative' && $unit->rh === 'positive') {
            return 'Rh: a positive unit cannot go to an Rh-negative patient.';
        }

        return null;
    }

    /**
     * Cross-matches one unit against the request's accepted sample and, when compatible, reserves it. The row is kept either way. An incompatible
     * result throws, so no caller can carry on and reserve the unit.
     */
    public function crossmatchAndReserve(BloodRequest $request, BloodUnit $unit, User $by, string $method = 'electronic'): BloodReservation
    {
        $outcome = DB::transaction(function () use ($request, $unit, $by, $method) {
            $lockedRequest = BloodRequest::query()->lockForUpdate()->findOrFail($request->id);
            $lockedUnit = BloodUnit::query()->lockForUpdate()->findOrFail($unit->id);
            if (! $lockedRequest->isOpen()) {
                throw ValidationException::withMessages(['request' => 'This request is closed.']);
            }
            if ($lockedUnit->status !== BloodUnit::AVAILABLE) {
                throw ValidationException::withMessages(['unit' => "Unit {$lockedUnit->unit_number} is {$lockedUnit->status} and cannot be reserved."]);
            }
            $patient = Patient::findOrFail($lockedRequest->patient_id);
            $sample = BloodSample::query()->where('request_id', $lockedRequest->id)->where('status', 'accepted')->where('expires_at', '>', now())->latest('id')->first()
                ?? BloodSample::query()->where('patient_id', $patient->id)->where('status', 'accepted')->where('expires_at', '>', now())->latest('id')->first();
            $group = $sample ? ['abo' => $sample->abo, 'rh' => $sample->rh] : null;
            if (! $group) {
                throw ValidationException::withMessages(['sample' => 'There is no valid, accepted sample for this patient. Receive a sample first.']);
            }
            $active = $lockedRequest->reservations()->where('status', 'active')->count() + $lockedRequest->issues()->whereIn('status', ['issued', 'transfusing', 'transfused'])->count();
            if ($active >= $lockedRequest->units_requested) {
                throw ValidationException::withMessages(['request' => 'All the units asked for are already reserved or issued.']);
            }

            $reason = $this->incompatibility($lockedRequest, $lockedUnit, $group);
            $match = BloodCrossmatch::create([
                'organization_id' => $lockedRequest->organization_id, 'request_id' => $lockedRequest->id, 'unit_id' => $lockedUnit->id, 'sample_id' => $sample->id, 'method' => $method,
                'result' => $reason ? 'incompatible' : 'compatible', 'reason' => $reason, 'performed_at' => now(), 'performed_by' => $by->id,
            ]);
            $this->units->event($lockedUnit, 'crossmatched', $by, $reason ?? 'Compatible', $match);
            if ($reason) {
                return $reason; // the incompatible result is kept (committed); the caller is told no below
            }

            $hours = (int) ($this->policy->get('reservation_hours')[$lockedRequest->priority] ?? 24);
            $reservation = BloodReservation::create([
                'organization_id' => $lockedRequest->organization_id, 'request_id' => $lockedRequest->id, 'unit_id' => $lockedUnit->id, 'crossmatch_id' => $match->id,
                'reserved_until' => now()->addHours($hours), 'reserved_by' => $by->id,
            ]);
            $this->units->move($lockedUnit, BloodUnit::RESERVED, $by, "Reserved for {$lockedRequest->request_number}", $reservation);
            $lockedRequest->update(['status' => $lockedRequest->reservations()->where('status', 'active')->count() >= $lockedRequest->units_requested ? 'ready' : 'sample_received']);

            $this->charges->publish(new ChargeEventData(
                organizationId: $lockedRequest->organization_id, branchId: (int) ($lockedRequest->branch_id ?? 0), patientId: $patient->id, module: 'BLD', serviceCode: 'BLD-XM',
                sourceEventId: $match->id, visitRef: $lockedRequest->request_number, encounterId: $lockedRequest->encounter_id, performedAt: now(), performedBy: $by->id,
                sourceType: BloodCrossmatch::class, sourceId: $match->id,
            ));

            return $reservation;
        });

        if (is_string($outcome)) {
            throw ValidationException::withMessages(['unit' => $outcome]);
        }

        return $outcome;
    }
}
