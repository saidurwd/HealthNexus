<?php

namespace App\Services\Ot;

use App\Models\Ot\OtTheatre;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place ot_theatres.status is ever written — mirrors IpdBedStatusService exactly.
 * Callers (scheduling, reception, cleaning-turnover) always go through this, never
 * OtTheatre::update(['status' => ...]) directly.
 */
class OtTheatreStatusService
{
    private const TRANSITIONS = [
        OtTheatre::STATUS_AVAILABLE => [OtTheatre::STATUS_OCCUPIED, OtTheatre::STATUS_BLOCKED, OtTheatre::STATUS_MAINTENANCE],
        OtTheatre::STATUS_OCCUPIED => [OtTheatre::STATUS_CLEANING, OtTheatre::STATUS_AVAILABLE],
        OtTheatre::STATUS_CLEANING => [OtTheatre::STATUS_AVAILABLE, OtTheatre::STATUS_MAINTENANCE, OtTheatre::STATUS_BLOCKED],
        OtTheatre::STATUS_BLOCKED => [OtTheatre::STATUS_AVAILABLE, OtTheatre::STATUS_MAINTENANCE],
        OtTheatre::STATUS_MAINTENANCE => [OtTheatre::STATUS_AVAILABLE, OtTheatre::STATUS_BLOCKED],
    ];

    public function transitionTo(OtTheatre $theatre, string $status): OtTheatre
    {
        if ($status === $theatre->status) {
            return $theatre;
        }

        if (! in_array($status, self::TRANSITIONS[$theatre->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move theatre '{$theatre->theatre_code}' from '{$theatre->status}' to '{$status}'.",
            ]);
        }

        $theatre->update(['status' => $status]);

        return $theatre->refresh();
    }

    public function lockAndTransitionFromAvailable(int $theatreId, string $status): OtTheatre
    {
        return DB::transaction(function () use ($theatreId, $status) {
            $theatre = OtTheatre::query()->whereKey($theatreId)->lockForUpdate()->firstOrFail();

            if ($theatre->status !== OtTheatre::STATUS_AVAILABLE) {
                throw ValidationException::withMessages([
                    'theatre' => "Theatre '{$theatre->theatre_code}' is not available (current status: '{$theatre->status}').",
                ]);
            }

            return $this->transitionTo($theatre, $status);
        });
    }
}
