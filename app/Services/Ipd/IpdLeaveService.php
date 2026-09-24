<?php

namespace App\Services\Ipd;

use App\Events\Ipd\PatientLeaveStarted;
use App\Events\Ipd\PatientReturnedFromLeave;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedAllocation;
use App\Models\Ipd\IpdBedMovement;
use App\Models\Ipd\IpdPatientLeave;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bed handling during leave is configurable (spec §37) — 'retain' keeps the allocation active
 * the whole time; 'release' frees the bed immediately (never through Cleaning, since the patient
 * is expected back) and re-allocates a bed (the same one if still available, otherwise another)
 * on return.
 */
class IpdLeaveService
{
    public function __construct(
        private readonly IpdBedAllocationService $allocation,
        private readonly SettingsService $settings,
    ) {}

    public function request(IpdAdmission $admission, array $data, User $user): IpdPatientLeave
    {
        return IpdPatientLeave::create([
            ...$data,
            'company_id' => $admission->company_id,
            'branch_id' => $admission->branch_id,
            'admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
            'requested_at' => $data['requested_at'] ?? now(),
            'status' => IpdPatientLeave::STATUS_REQUESTED,
            'bed_handling' => $data['bed_handling'] ?? $this->settings->get('ipd.leave_default_bed_handling', 'retain'),
            'requested_by' => $user->id,
        ]);
    }

    public function approve(IpdPatientLeave $leave, User $user): IpdPatientLeave
    {
        if ($leave->status !== IpdPatientLeave::STATUS_REQUESTED) {
            throw ValidationException::withMessages(['leave' => "This leave is already '{$leave->status}'."]);
        }

        $leave->update(['status' => IpdPatientLeave::STATUS_APPROVED, 'approved_by' => $user->id]);

        return $leave->refresh();
    }

    public function start(IpdPatientLeave $leave, User $user): IpdPatientLeave
    {
        return DB::transaction(function () use ($leave, $user) {
            if ($leave->status !== IpdPatientLeave::STATUS_APPROVED) {
                throw ValidationException::withMessages(['leave' => "This leave must be approved before it can start (currently '{$leave->status}')."]);
            }

            $admission = $leave->admission;
            $currentAllocation = $admission->currentAllocation()->first();

            if ($currentAllocation) {
                IpdBedMovement::create([
                    'company_id' => $admission->company_id,
                    'branch_id' => $admission->branch_id,
                    'admission_id' => $admission->id,
                    'patient_id' => $admission->patient_id,
                    'from_bed_id' => $currentAllocation->bed_id,
                    'to_bed_id' => null,
                    'movement_type' => IpdBedMovement::TYPE_TEMPORARY_LEAVE,
                    'requested_at' => $leave->requested_at,
                    'moved_at' => now(),
                    'status' => IpdBedMovement::STATUS_COMPLETED,
                    'reason' => $leave->reason,
                    'requested_by' => $leave->requested_by,
                    'completed_by' => $user->id,
                ]);

                if ($leave->bed_handling === IpdPatientLeave::BED_HANDLING_RELEASE) {
                    $this->allocation->release($currentAllocation, $user, 'Patient on leave', requiresCleaning: false);
                }
            }

            $leave->update(['status' => IpdPatientLeave::STATUS_ON_LEAVE]);

            event(new PatientLeaveStarted($leave));

            return $leave->refresh();
        });
    }

    public function markReturned(IpdPatientLeave $leave, User $user, ?IpdBed $bed = null): IpdPatientLeave
    {
        return DB::transaction(function () use ($leave, $user, $bed) {
            if ($leave->status !== IpdPatientLeave::STATUS_ON_LEAVE) {
                throw ValidationException::withMessages(['leave' => "This leave is not currently active (status '{$leave->status}')."]);
            }

            $admission = $leave->admission;

            if ($leave->bed_handling === IpdPatientLeave::BED_HANDLING_RELEASE) {
                if (! $bed) {
                    throw ValidationException::withMessages(['bed' => 'A bed must be selected to complete this return, since the original bed was released during leave.']);
                }

                $this->allocation->allocate($admission, $bed, $user, IpdBedAllocation::TYPE_LEAVE_RETURN, 'Returned from leave');

                IpdBedMovement::create([
                    'company_id' => $admission->company_id,
                    'branch_id' => $admission->branch_id,
                    'admission_id' => $admission->id,
                    'patient_id' => $admission->patient_id,
                    'from_bed_id' => null,
                    'to_bed_id' => $bed->id,
                    'movement_type' => IpdBedMovement::TYPE_RETURN,
                    'requested_at' => now(),
                    'moved_at' => now(),
                    'status' => IpdBedMovement::STATUS_COMPLETED,
                    'requested_by' => $user->id,
                    'completed_by' => $user->id,
                ]);
            }

            $leave->update(['status' => IpdPatientLeave::STATUS_RETURNED, 'actual_return_at' => now()]);

            event(new PatientReturnedFromLeave($leave));

            return $leave->refresh();
        });
    }

    public function cancel(IpdPatientLeave $leave, User $user): IpdPatientLeave
    {
        if (! in_array($leave->status, [IpdPatientLeave::STATUS_REQUESTED, IpdPatientLeave::STATUS_APPROVED], true)) {
            throw ValidationException::withMessages(['leave' => "This leave is already '{$leave->status}' and cannot be cancelled."]);
        }

        $leave->update(['status' => IpdPatientLeave::STATUS_CANCELLED]);

        return $leave->refresh();
    }
}
