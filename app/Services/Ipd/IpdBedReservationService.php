<?php

namespace App\Services\Ipd;

use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedReservation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IpdBedReservationService
{
    public function __construct(private readonly IpdBedStatusService $bedStatus) {}

    public function reserve(IpdBed $bed, Patient $patient, User $user, ?int $admissionRequestId = null, ?string $reason = null, ?int $expiryMinutes = null): IpdBedReservation
    {
        return DB::transaction(function () use ($bed, $patient, $user, $admissionRequestId, $reason, $expiryMinutes) {
            $locked = $this->bedStatus->lockAndTransitionFromAvailable($bed->id, IpdBed::STATUS_RESERVED, $user, $reason);

            return IpdBedReservation::create([
                'company_id' => $locked->company_id,
                'branch_id' => $locked->branch_id,
                'patient_id' => $patient->id,
                'admission_request_id' => $admissionRequestId,
                'bed_id' => $locked->id,
                'reserved_at' => now(),
                'expires_at' => now()->addMinutes($expiryMinutes ?? 120),
                'status' => IpdBedReservation::STATUS_RESERVED,
                'reason' => $reason,
                'requested_by' => $user->id,
            ]);
        });
    }

    public function cancel(IpdBedReservation $reservation, User $user): IpdBedReservation
    {
        return DB::transaction(function () use ($reservation, $user) {
            if ($reservation->status !== IpdBedReservation::STATUS_RESERVED) {
                throw ValidationException::withMessages(['reservation' => "This reservation is already '{$reservation->status}'."]);
            }

            $bed = IpdBed::query()->whereKey($reservation->bed_id)->lockForUpdate()->firstOrFail();
            $this->bedStatus->transitionTo($bed, IpdBed::STATUS_AVAILABLE, $user);

            $reservation->update(['status' => IpdBedReservation::STATUS_CANCELLED]);

            return $reservation->refresh();
        });
    }

    public function expire(IpdBedReservation $reservation): IpdBedReservation
    {
        return DB::transaction(function () use ($reservation) {
            if ($reservation->status !== IpdBedReservation::STATUS_RESERVED) {
                return $reservation;
            }

            $bed = IpdBed::query()->whereKey($reservation->bed_id)->lockForUpdate()->first();

            if ($bed && $bed->status === IpdBed::STATUS_RESERVED) {
                $this->bedStatus->transitionTo($bed, IpdBed::STATUS_AVAILABLE);
            }

            $reservation->update(['status' => IpdBedReservation::STATUS_EXPIRED]);

            return $reservation->refresh();
        });
    }

    public function markConverted(IpdBedReservation $reservation): IpdBedReservation
    {
        $reservation->update(['status' => IpdBedReservation::STATUS_CONVERTED]);

        return $reservation->refresh();
    }
}
