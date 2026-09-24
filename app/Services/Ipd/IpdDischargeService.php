<?php

namespace App\Services\Ipd;

use App\Events\Ipd\DischargeRequested;
use App\Events\Ipd\PatientDischarged;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdDischargeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Discharge planning + the discharge record are folded into one ipd_discharge_requests row
 * (spec's three suggested tables collapsed to one, per plan decision #10). Clinical/billing/
 * pharmacy clearance are independent, order-agnostic flags; the request becomes 'approved' once
 * all three are recorded. complete() is the transactional finisher: releases the bed, records the
 * final movement, and closes the admission.
 */
class IpdDischargeService
{
    public const CLEARANCE_CLINICAL = 'clinical';
    public const CLEARANCE_BILLING = 'billing';
    public const CLEARANCE_PHARMACY = 'pharmacy';

    public function __construct(
        private readonly IpdBedAllocationService $allocation,
        private readonly IpdAdmissionLifecycleService $lifecycle,
    ) {}

    public function request(IpdAdmission $admission, array $data, User $user): IpdDischargeRequest
    {
        return DB::transaction(function () use ($admission, $data, $user) {
            $request = IpdDischargeRequest::create([
                ...$data,
                'company_id' => $admission->company_id,
                'branch_id' => $admission->branch_id,
                'admission_id' => $admission->id,
                'patient_id' => $admission->patient_id,
                'status' => IpdDischargeRequest::STATUS_REQUESTED,
                'requested_by' => $user->id,
            ]);

            $this->lifecycle->transitionTo($admission, 'discharge_planned');

            event(new DischargeRequested($request));

            return $request;
        });
    }

    public function recordClearance(IpdDischargeRequest $request, string $type, User $user): IpdDischargeRequest
    {
        if (! in_array($type, [self::CLEARANCE_CLINICAL, self::CLEARANCE_BILLING, self::CLEARANCE_PHARMACY], true)) {
            throw ValidationException::withMessages(['type' => "Unknown clearance type '{$type}'."]);
        }

        return DB::transaction(function () use ($request, $type, $user) {
            $request->update(["{$type}_cleared_at" => now(), "{$type}_cleared_by" => $user->id]);
            $request->refresh();

            if ($request->clinical_cleared_at && $request->billing_cleared_at && $request->pharmacy_cleared_at) {
                $request->update(['status' => IpdDischargeRequest::STATUS_APPROVED, 'approved_by' => $user->id, 'approved_at' => now()]);

                $admission = $request->admission;
                if ($admission->status !== 'discharge_pending') {
                    $this->lifecycle->transitionTo($admission, 'discharge_pending');
                }
            }

            return $request->refresh();
        });
    }

    public function complete(IpdDischargeRequest $request, User $user): IpdDischargeRequest
    {
        return DB::transaction(function () use ($request, $user) {
            if ($request->status !== IpdDischargeRequest::STATUS_APPROVED) {
                throw ValidationException::withMessages(['request' => "This discharge request must be approved (all clearances recorded) before it can be completed — currently '{$request->status}'."]);
            }

            $admission = $request->admission;
            $currentAllocation = $admission->currentAllocation()->first();

            if ($currentAllocation) {
                $this->allocation->release($currentAllocation, $user, 'Discharge completed');
            }

            $admission->update([
                'actual_discharge_date' => now(),
                'discharged_by' => $user->id,
                'discharge_disposition_id' => $request->disposition_id,
            ]);
            $this->lifecycle->transitionTo($admission, 'discharged');

            $request->update(['status' => IpdDischargeRequest::STATUS_COMPLETED, 'completed_at' => now()]);

            event(new PatientDischarged($admission->fresh()));

            return $request->refresh();
        });
    }
}
