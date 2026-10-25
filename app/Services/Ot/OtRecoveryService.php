<?php

namespace App\Services\Ot;

use App\Events\Ot\RecoveryCompleted;
use App\Events\Ot\SurgicalPatientTransferred;
use App\Models\Ot\OtRecoveryRecord;
use App\Models\Ot\OtSurgery;
use App\Models\User;
use App\Models\VitalSign;
use App\Services\Clinical\EncounterClinicalService;
use App\Services\Ipd\IpdAdmissionLifecycleService;
use Illuminate\Validation\ValidationException;

/**
 * Self-contained PACU workflow — never depends on NursingEpisode (decision #6), so it works
 * identically for IPD inpatients and pure day-surgery patients with no admission at all.
 * completeTransfer() never calls IpdAdmissionService::admit() itself (decision #8) — it only
 * transitions an admission that already exists.
 */
class OtRecoveryService
{
    public function __construct(
        private readonly OtSurgeryLifecycleService $lifecycle,
        private readonly EncounterClinicalService $clinical,
        private readonly IpdAdmissionLifecycleService $admissionLifecycle,
    ) {}

    public function admitToRecovery(OtSurgery $surgery): OtRecoveryRecord
    {
        $this->lifecycle->transitionTo($surgery, OtSurgery::STATUS_RECOVERY);

        return OtRecoveryRecord::firstOrCreate(
            ['surgery_id' => $surgery->id],
            ['patient_id' => $surgery->patient_id, 'arrived_at' => now(), 'status' => OtRecoveryRecord::STATUS_IN_RECOVERY],
        );
    }

    public function recordVitals(OtRecoveryRecord $recovery, array $data, User $user): VitalSign
    {
        return $this->clinical->addVital($recovery->surgery->encounter, $data, $user);
    }

    public function updateAssessment(OtRecoveryRecord $recovery, array $data): OtRecoveryRecord
    {
        $recovery->update($data);

        return $recovery->refresh();
    }

    public function markReadyForTransfer(OtRecoveryRecord $recovery): OtRecoveryRecord
    {
        if (! $recovery->discharge_criteria_met) {
            throw ValidationException::withMessages(['recovery' => 'Discharge criteria must be confirmed met before the patient can be marked ready for transfer.']);
        }

        $recovery->update(['status' => OtRecoveryRecord::STATUS_READY_FOR_TRANSFER]);

        return $recovery->refresh();
    }

    public function completeTransfer(OtRecoveryRecord $recovery, string $destination, User $user): OtRecoveryRecord
    {
        $surgery = $recovery->surgery;

        $recovery->update([
            'status' => $destination === OtRecoveryRecord::DESTINATION_DISCHARGE ? OtRecoveryRecord::STATUS_DISCHARGED : OtRecoveryRecord::STATUS_TRANSFERRED,
            'destination' => $destination,
            'transferred_at' => now(),
            'transferred_by' => $user->id,
            'discharged_at' => $destination === OtRecoveryRecord::DESTINATION_DISCHARGE ? now() : null,
        ]);

        $this->lifecycle->transitionTo($surgery, OtSurgery::STATUS_TRANSFERRED);

        $admission = $surgery->admission;

        if ($admission && in_array($destination, [OtRecoveryRecord::DESTINATION_WARD, OtRecoveryRecord::DESTINATION_ICU, OtRecoveryRecord::DESTINATION_HDU], true)) {
            // The patient was already an IPD inpatient pre-op — hand them back to the ward via
            // Phase 8's own lifecycle service. OT never allocates a bed or creates an admission.
            if ($admission->status !== 'active') {
                $this->admissionLifecycle->transitionTo($admission, 'active');
            }
        } else {
            // A day-case needing unplanned admission, or any destination OT has no existing
            // admission for — only the intended destination is recorded; ward/admission staff
            // act on it through Phase 8's own existing flow.
            event(new SurgicalPatientTransferred($recovery->fresh()));
        }

        event(new RecoveryCompleted($recovery->fresh()));

        return $recovery->fresh();
    }
}
