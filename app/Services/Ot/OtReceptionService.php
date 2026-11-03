<?php

namespace App\Services\Ot;

use App\Events\Ot\PatientReceivedForSurgery;
use App\Events\Ot\SurgeryCompleted;
use App\Events\Ot\SurgeryStarted;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgicalSafetyChecklist;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Orchestrates "OT reception" (the patient physically entering the theatre suite — never to be
 * confused with Phase 8 hospital admission, decision #14) through to procedure completion.
 * Enforces the WHO checklist gates: sign_in before in_procedure, sign_out before
 * procedure_completed (spec §24/§28).
 */
class OtReceptionService
{
    public function __construct(
        private readonly OtSurgeryLifecycleService $lifecycle,
        private readonly OtSurgicalSafetyChecklistService $checklist,
    ) {}

    public function receive(OtSurgery $surgery, User $user): OtSurgery
    {
        $this->lifecycle->transitionTo($surgery, OtSurgery::STATUS_PATIENT_RECEIVED);
        $surgery->update(['received_at' => now()]);

        event(new PatientReceivedForSurgery($surgery->fresh()));

        return $surgery->fresh();
    }

    public function start(OtSurgery $surgery, User $user): OtSurgery
    {
        return DB::transaction(function () use ($surgery) {
            if (! $this->checklist->isPhaseComplete($surgery, OtSurgicalSafetyChecklist::PHASE_SIGN_IN)) {
                throw ValidationException::withMessages(['checklist' => 'The Sign In safety checklist must be completed before the procedure can start.']);
            }

            $this->lifecycle->transitionTo($surgery, OtSurgery::STATUS_IN_PROCEDURE);
            $surgery->update(['actual_start_at' => now()]);

            event(new SurgeryStarted($surgery->fresh()));

            return $surgery->fresh();
        });
    }

    public function complete(OtSurgery $surgery, User $user): OtSurgery
    {
        return DB::transaction(function () use ($surgery, $user) {
            if (! $this->checklist->isPhaseComplete($surgery, OtSurgicalSafetyChecklist::PHASE_SIGN_OUT)) {
                throw ValidationException::withMessages(['checklist' => 'The Sign Out safety checklist must be completed before the procedure can be marked complete.']);
            }

            $this->lifecycle->transitionTo($surgery, OtSurgery::STATUS_PROCEDURE_COMPLETED);
            $surgery->update(['actual_end_at' => now(), 'completed_by' => $user->id, 'completed_at' => now()]);

            event(new SurgeryCompleted($surgery->fresh()));

            return $surgery->fresh();
        });
    }

    public function abort(OtSurgery $surgery, User $user, string $reason): OtSurgery
    {
        $this->lifecycle->transitionTo($surgery, OtSurgery::STATUS_ABORTED);
        $surgery->update(['actual_end_at' => now(), 'cancellation_reason' => $reason]);

        return $surgery->fresh();
    }
}
