<?php

namespace App\Services\Ot;

use App\Models\Ot\OtSurgery;
use Illuminate\Validation\ValidationException;

/**
 * The only place ot_surgeries.status is ever written directly — mirrors
 * IpdAdmissionLifecycleService's const-table shape exactly.
 */
class OtSurgeryLifecycleService
{
    private const TRANSITIONS = [
        OtSurgery::STATUS_REQUESTED => [OtSurgery::STATUS_UNDER_REVIEW, OtSurgery::STATUS_APPROVED, OtSurgery::STATUS_REJECTED, OtSurgery::STATUS_CANCELLED],
        OtSurgery::STATUS_UNDER_REVIEW => [OtSurgery::STATUS_APPROVED, OtSurgery::STATUS_REJECTED, OtSurgery::STATUS_CANCELLED],
        OtSurgery::STATUS_APPROVED => [OtSurgery::STATUS_SCHEDULED, OtSurgery::STATUS_CANCELLED, OtSurgery::STATUS_POSTPONED],
        // 'preoperative_preparation' and 'ready_for_ot' are informational sub-states with no
        // dedicated transition action in this phase — OtReceptionService::receive() is the sole
        // entry point into 'patient_received', so 'scheduled' (and its intermediate states) may
        // all reach it directly rather than forcing every caller through unused stepping stones.
        OtSurgery::STATUS_SCHEDULED => [OtSurgery::STATUS_PREOPERATIVE_PREPARATION, OtSurgery::STATUS_PATIENT_RECEIVED, OtSurgery::STATUS_CANCELLED, OtSurgery::STATUS_POSTPONED, OtSurgery::STATUS_SCHEDULED],
        OtSurgery::STATUS_PREOPERATIVE_PREPARATION => [OtSurgery::STATUS_READY_FOR_OT, OtSurgery::STATUS_PATIENT_RECEIVED, OtSurgery::STATUS_CANCELLED, OtSurgery::STATUS_POSTPONED],
        OtSurgery::STATUS_READY_FOR_OT => [OtSurgery::STATUS_PATIENT_RECEIVED, OtSurgery::STATUS_NO_SHOW, OtSurgery::STATUS_CANCELLED, OtSurgery::STATUS_POSTPONED],
        OtSurgery::STATUS_PATIENT_RECEIVED => [OtSurgery::STATUS_IN_PROCEDURE, OtSurgery::STATUS_CANCELLED],
        OtSurgery::STATUS_IN_PROCEDURE => [OtSurgery::STATUS_PROCEDURE_COMPLETED, OtSurgery::STATUS_ABORTED],
        OtSurgery::STATUS_PROCEDURE_COMPLETED => [OtSurgery::STATUS_RECOVERY],
        OtSurgery::STATUS_ABORTED => [OtSurgery::STATUS_RECOVERY, OtSurgery::STATUS_CLOSED],
        OtSurgery::STATUS_RECOVERY => [OtSurgery::STATUS_TRANSFERRED],
        OtSurgery::STATUS_TRANSFERRED => [OtSurgery::STATUS_CLOSED],
        OtSurgery::STATUS_CLOSED => [],
        OtSurgery::STATUS_CANCELLED => [],
        OtSurgery::STATUS_POSTPONED => [OtSurgery::STATUS_SCHEDULED, OtSurgery::STATUS_CANCELLED],
        OtSurgery::STATUS_REJECTED => [],
        OtSurgery::STATUS_NO_SHOW => [OtSurgery::STATUS_POSTPONED, OtSurgery::STATUS_CANCELLED],
    ];

    public function transitionTo(OtSurgery $surgery, string $status): OtSurgery
    {
        if ($status === $surgery->status) {
            return $surgery;
        }

        if (! in_array($status, self::TRANSITIONS[$surgery->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move surgery '{$surgery->surgery_number}' from '{$surgery->status}' to '{$status}'.",
            ]);
        }

        $surgery->update(['status' => $status]);

        return $surgery->refresh();
    }
}
