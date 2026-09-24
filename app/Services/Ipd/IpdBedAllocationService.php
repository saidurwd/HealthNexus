<?php

namespace App\Services\Ipd;

use App\Events\Ipd\BedAllocated;
use App\Events\Ipd\BedReleased;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedAllocation;
use App\Models\Ipd\IpdBedMovement;
use App\Models\Ipd\IpdBedReservation;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The single highest-risk concurrency surface in the module (spec §13/§53/§54): allocate() locks
 * the bed row inside DB::transaction(), re-verifies eligibility under the lock, then creates the
 * allocation and flips bed status — identical shape to PharmacyStockService::debit(). Two
 * concurrent allocation attempts against the same bed can never both succeed; the second blocks
 * on the row lock and then fails its own re-check once it proceeds.
 */
class IpdBedAllocationService
{
    public function __construct(
        private readonly IpdBedStatusService $bedStatus,
        private readonly SettingsService $settings,
    ) {}

    public function allocate(IpdAdmission $admission, IpdBed $bed, User $user, string $allocationType = IpdBedAllocation::TYPE_ADMISSION, ?string $reason = null): IpdBedAllocation
    {
        return DB::transaction(function () use ($admission, $bed, $user, $allocationType, $reason) {
            $locked = IpdBed::query()->whereKey($bed->id)->lockForUpdate()->firstOrFail();

            $this->assertEligible($locked, $admission);

            $this->bedStatus->transitionTo($locked, IpdBed::STATUS_OCCUPIED, $user, $reason);

            $allocation = IpdBedAllocation::create([
                'company_id' => $admission->company_id,
                'branch_id' => $admission->branch_id,
                'admission_id' => $admission->id,
                'patient_id' => $admission->patient_id,
                'bed_id' => $locked->id,
                'allocated_at' => now(),
                'status' => IpdBedAllocation::STATUS_ACTIVE,
                'allocation_type' => $allocationType,
                'reason' => $reason,
                'allocated_by' => $user->id,
            ]);

            IpdBedMovement::create([
                'company_id' => $admission->company_id,
                'branch_id' => $admission->branch_id,
                'admission_id' => $admission->id,
                'patient_id' => $admission->patient_id,
                'from_bed_id' => null,
                'to_bed_id' => $locked->id,
                'movement_type' => IpdBedMovement::TYPE_ADMISSION,
                'requested_at' => now(),
                'moved_at' => now(),
                'status' => IpdBedMovement::STATUS_COMPLETED,
                'completed_by' => $user->id,
                'reason' => $reason,
            ]);

            IpdBedReservation::query()
                ->where('bed_id', $locked->id)
                ->where('patient_id', $admission->patient_id)
                ->where('status', IpdBedReservation::STATUS_RESERVED)
                ->get()
                ->each(fn (IpdBedReservation $r) => $r->update(['status' => IpdBedReservation::STATUS_CONVERTED]));

            event(new BedAllocated($allocation));

            return $allocation->refresh();
        });
    }

    public function release(IpdBedAllocation $allocation, User $user, ?string $reason = null, ?bool $requiresCleaning = null): IpdBedAllocation
    {
        return DB::transaction(function () use ($allocation, $user, $reason, $requiresCleaning) {
            if ($allocation->released_at !== null) {
                throw ValidationException::withMessages(['allocation' => 'This allocation is already released.']);
            }

            $bed = IpdBed::query()->whereKey($allocation->bed_id)->lockForUpdate()->firstOrFail();

            $requiresCleaning ??= $this->settings->get('ipd.discharge_requires_cleaning', true);
            $this->bedStatus->transitionTo($bed, $requiresCleaning ? IpdBed::STATUS_CLEANING : IpdBed::STATUS_AVAILABLE, $user, $reason);

            $allocation->update(['released_at' => now(), 'status' => IpdBedAllocation::STATUS_RELEASED, 'released_by' => $user->id]);

            event(new BedReleased($allocation));

            return $allocation->refresh();
        });
    }

    /**
     * Must be called with the bed row already locked (lockForUpdate()) by the caller — verifies
     * status, gender, and one-active-allocation-per-admission eligibility. Never trusts a
     * pre-lock read of bed status.
     */
    public function assertEligible(IpdBed $lockedBed, IpdAdmission $admission): void
    {
        if ($lockedBed->status === IpdBed::STATUS_RESERVED) {
            $reservedForThisPatient = IpdBedReservation::query()
                ->where('bed_id', $lockedBed->id)
                ->where('patient_id', $admission->patient_id)
                ->where('status', IpdBedReservation::STATUS_RESERVED)
                ->exists();

            if (! $reservedForThisPatient) {
                throw ValidationException::withMessages(['bed' => "Bed '{$lockedBed->bed_code}' is reserved for another patient."]);
            }
        } elseif ($lockedBed->status !== IpdBed::STATUS_AVAILABLE) {
            throw ValidationException::withMessages(['bed' => "Bed '{$lockedBed->bed_code}' is not available (current status: '{$lockedBed->status}')."]);
        }

        $patientGender = $admission->patient?->gender?->code;

        if ($lockedBed->gender_type !== 'any' && $patientGender && in_array($patientGender, ['male', 'female'], true) && $lockedBed->gender_type !== $patientGender) {
            throw ValidationException::withMessages(['bed' => "Bed '{$lockedBed->bed_code}' is restricted to {$lockedBed->gender_type} patients."]);
        }

        $hasActiveAllocation = IpdBedAllocation::query()
            ->where('admission_id', $admission->id)
            ->whereNull('released_at')
            ->exists();

        if ($hasActiveAllocation) {
            throw ValidationException::withMessages(['admission' => 'This admission already has an active bed allocation.']);
        }
    }
}
