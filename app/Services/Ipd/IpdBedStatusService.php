<?php

namespace App\Services\Ipd;

use App\Models\Ipd\IpdBed;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place ipd_beds.status is ever written. Every transition is locked
 * (lockForUpdate()) and validated against TRANSITIONS — callers (allocation, reservation,
 * transfer, discharge, leave, blocking services) always go through this, never
 * IpdBed::update(['status' => ...]) directly, per spec §12: "Never directly modify a bed's
 * status without recording the corresponding movement/state transition." The movement/
 * allocation/reservation/block row created by the calling service IS that record — this
 * service only owns the status field itself and its validity.
 */
class IpdBedStatusService
{
    private const TRANSITIONS = [
        IpdBed::STATUS_AVAILABLE => [IpdBed::STATUS_RESERVED, IpdBed::STATUS_OCCUPIED, IpdBed::STATUS_BLOCKED, IpdBed::STATUS_MAINTENANCE, IpdBed::STATUS_OUT_OF_SERVICE],
        IpdBed::STATUS_RESERVED => [IpdBed::STATUS_AVAILABLE, IpdBed::STATUS_OCCUPIED, IpdBed::STATUS_BLOCKED],
        IpdBed::STATUS_OCCUPIED => [IpdBed::STATUS_CLEANING, IpdBed::STATUS_AVAILABLE, IpdBed::STATUS_PENDING_TRANSFER, IpdBed::STATUS_PENDING_DISCHARGE, IpdBed::STATUS_ISOLATION],
        IpdBed::STATUS_ISOLATION => [IpdBed::STATUS_OCCUPIED, IpdBed::STATUS_CLEANING, IpdBed::STATUS_AVAILABLE],
        IpdBed::STATUS_PENDING_TRANSFER => [IpdBed::STATUS_OCCUPIED, IpdBed::STATUS_CLEANING, IpdBed::STATUS_AVAILABLE],
        IpdBed::STATUS_PENDING_DISCHARGE => [IpdBed::STATUS_OCCUPIED, IpdBed::STATUS_CLEANING, IpdBed::STATUS_AVAILABLE],
        IpdBed::STATUS_CLEANING => [IpdBed::STATUS_AVAILABLE, IpdBed::STATUS_MAINTENANCE, IpdBed::STATUS_OUT_OF_SERVICE, IpdBed::STATUS_BLOCKED],
        IpdBed::STATUS_BLOCKED => [IpdBed::STATUS_AVAILABLE, IpdBed::STATUS_MAINTENANCE, IpdBed::STATUS_OUT_OF_SERVICE],
        IpdBed::STATUS_MAINTENANCE => [IpdBed::STATUS_AVAILABLE, IpdBed::STATUS_BLOCKED, IpdBed::STATUS_OUT_OF_SERVICE],
        IpdBed::STATUS_OUT_OF_SERVICE => [IpdBed::STATUS_AVAILABLE, IpdBed::STATUS_MAINTENANCE],
    ];

    /**
     * Must be called from inside a transaction that already holds (or is about to take)
     * lockForUpdate() on this bed row — callers pass the already-locked instance.
     */
    public function transitionTo(IpdBed $bed, string $status, ?User $user = null, ?string $reason = null): IpdBed
    {
        if ($status === $bed->status) {
            return $bed;
        }

        if (! in_array($status, self::TRANSITIONS[$bed->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move bed '{$bed->bed_code}' from '{$bed->status}' to '{$status}'.",
            ]);
        }

        $bed->update(['status' => $status]);

        return $bed->refresh();
    }

    /**
     * Locks the bed row, re-checks it is genuinely available, and transitions it — the single
     * entry point every allocation/reservation call funnels through so the check-then-act is
     * atomic under concurrent requests.
     */
    public function lockAndTransitionFromAvailable(int $bedId, string $status, ?User $user = null, ?string $reason = null): IpdBed
    {
        return DB::transaction(function () use ($bedId, $status, $user, $reason) {
            $bed = IpdBed::query()->whereKey($bedId)->lockForUpdate()->firstOrFail();

            if ($bed->status !== IpdBed::STATUS_AVAILABLE) {
                throw ValidationException::withMessages([
                    'bed' => "Bed '{$bed->bed_code}' is not available (current status: '{$bed->status}').",
                ]);
            }

            return $this->transitionTo($bed, $status, $user, $reason);
        });
    }
}
