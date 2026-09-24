<?php

namespace App\Services\Ipd;

use App\Events\Ipd\PatientTransferred;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedAllocation;
use App\Models\Ipd\IpdBedMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A transfer is a movement row (spec §9 decision) — never a separate ipd_transfers table.
 * complete() is the atomic core (spec §15/§83): both source and destination bed rows are locked
 * in a single query ordered by ascending id (never two separate lock acquisitions), so a
 * concurrent reverse-direction transfer can never deadlock against this one. The patient is never
 * left occupying two active beds — release() and allocate() run inside the same transaction that
 * already holds both locks.
 */
class IpdTransferService
{
    public function __construct(
        private readonly IpdBedAllocationService $allocation,
        private readonly IpdAdmissionLifecycleService $lifecycle,
    ) {}

    public function request(IpdAdmission $admission, IpdBed $destinationBed, User $user, string $movementType = IpdBedMovement::TYPE_TRANSFER, ?string $reason = null): IpdBedMovement
    {
        return DB::transaction(function () use ($admission, $destinationBed, $user, $movementType, $reason) {
            $currentAllocation = $admission->currentAllocation()->first();

            if (! $currentAllocation) {
                throw ValidationException::withMessages(['admission' => 'This admission has no active bed to transfer from.']);
            }

            if ($destinationBed->id === $currentAllocation->bed_id) {
                throw ValidationException::withMessages(['bed' => 'The destination bed must differ from the current bed.']);
            }

            $movement = IpdBedMovement::create([
                'company_id' => $admission->company_id,
                'branch_id' => $admission->branch_id,
                'admission_id' => $admission->id,
                'patient_id' => $admission->patient_id,
                'from_bed_id' => $currentAllocation->bed_id,
                'to_bed_id' => $destinationBed->id,
                'movement_type' => $movementType,
                'requested_at' => now(),
                'status' => IpdBedMovement::STATUS_REQUESTED,
                'reason' => $reason,
                'requested_by' => $user->id,
            ]);

            $this->lifecycle->transitionTo($admission, 'transfer_requested');

            return $movement;
        });
    }

    public function approve(IpdBedMovement $movement, User $user): IpdBedMovement
    {
        return DB::transaction(function () use ($movement, $user) {
            if ($movement->status !== IpdBedMovement::STATUS_REQUESTED) {
                throw ValidationException::withMessages(['movement' => "This transfer is already '{$movement->status}'."]);
            }

            $movement->update(['status' => IpdBedMovement::STATUS_APPROVED, 'approved_at' => now(), 'approved_by' => $user->id]);

            return $movement->refresh();
        });
    }

    public function complete(IpdBedMovement $movement, User $user): IpdBedMovement
    {
        return DB::transaction(function () use ($movement, $user) {
            if (! in_array($movement->status, [IpdBedMovement::STATUS_REQUESTED, IpdBedMovement::STATUS_APPROVED], true)) {
                throw ValidationException::withMessages(['movement' => "This transfer is already '{$movement->status}'."]);
            }

            // Lock both source and destination in one query ordered by ascending id — never two
            // separate lock acquisitions, which is what prevents deadlock against a concurrent
            // reverse-direction transfer.
            $beds = IpdBed::query()
                ->whereIn('id', [$movement->from_bed_id, $movement->to_bed_id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $destinationBed = $beds->get($movement->to_bed_id);

            if (! $destinationBed) {
                throw ValidationException::withMessages(['bed' => 'The destination bed could not be found.']);
            }

            $admission = $movement->admission()->lockForUpdate()->first();
            $currentAllocation = $admission->currentAllocation()->first();

            if (! $currentAllocation || $currentAllocation->bed_id !== $movement->from_bed_id) {
                throw ValidationException::withMessages(['admission' => 'The admission is no longer occupying the expected source bed.']);
            }

            $this->allocation->release($currentAllocation, $user, 'Transferred to bed '.$destinationBed->bed_code);
            $this->allocation->allocate($admission, $destinationBed, $user, IpdBedAllocation::TYPE_TRANSFER, $movement->reason);

            $movement->update(['status' => IpdBedMovement::STATUS_COMPLETED, 'moved_at' => now(), 'completed_by' => $user->id]);

            $this->lifecycle->transitionTo($admission, 'transferred');
            $this->lifecycle->transitionTo($admission, 'active');

            event(new PatientTransferred($movement));

            return $movement->refresh();
        });
    }

    public function cancel(IpdBedMovement $movement, User $user, ?string $reason = null): IpdBedMovement
    {
        return DB::transaction(function () use ($movement, $user, $reason) {
            if (! in_array($movement->status, [IpdBedMovement::STATUS_REQUESTED, IpdBedMovement::STATUS_APPROVED], true)) {
                throw ValidationException::withMessages(['movement' => "This transfer is already '{$movement->status}'."]);
            }

            $movement->update(['status' => IpdBedMovement::STATUS_CANCELLED, 'reason' => $reason ?? $movement->reason]);

            $admission = $movement->admission;

            if ($admission->status === 'transfer_requested') {
                $this->lifecycle->transitionTo($admission, 'active');
            }

            return $movement->refresh();
        });
    }
}
