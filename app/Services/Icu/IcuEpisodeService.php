<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUPatientAdmitted;
use App\Models\Icu\IcuAdmissionRequest;
use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuEpisodeEvent;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\User;
use App\Services\Ipd\IpdAdmissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Orchestrates the "Bed Availability -> Admission/Transfer -> ICU Bed Assignment -> Phase 11 ICU
 * Admission" sequence from spec §5/§6. When the request already carries an admission_id (the
 * patient is already an inpatient and is escalating to ICU), this only reallocates the bed via
 * IcuBedAllocationService. When there is no existing admission, it calls Phase 8's own
 * IpdAdmissionService::admit() directly — ICU uses Phase 8's admission entry point, it never
 * builds a parallel one.
 */
class IcuEpisodeService
{
    public function __construct(
        private readonly IcuNumberGenerator $numbers,
        private readonly IcuBedAllocationService $bedAllocation,
        private readonly IpdAdmissionService $ipdAdmission,
    ) {}

    public function admit(IcuAdmissionRequest $request, array $bedData, User $user): IcuEpisode
    {
        return DB::transaction(function () use ($request, $bedData, $user) {
            if ($request->status !== IcuAdmissionRequest::STATUS_ACCEPTED) {
                throw ValidationException::withMessages(['request' => "This request must be accepted before admission (currently '{$request->status}')."]);
            }

            if ($request->episode()->exists()) {
                throw ValidationException::withMessages(['request' => 'This request has already resulted in an ICU episode.']);
            }

            $bed = IpdBed::query()->findOrFail($bedData['bed_id']);

            if (! $bed->icu_capable) {
                throw ValidationException::withMessages(['bed' => "Bed '{$bed->bed_code}' is not ICU-capable."]);
            }

            if ($request->admission_id) {
                $admission = IpdAdmission::query()->findOrFail($request->admission_id);
                $this->bedAllocation->assign($admission, $bed, $user, 'ICU admission');
            } else {
                $admission = $this->ipdAdmission->admit([
                    'company_id' => $request->company_id,
                    'branch_id' => $request->branch_id,
                    'patient_id' => $request->patient_id,
                    'specialty_id' => $bedData['specialty_id'] ?? null,
                    'admitting_provider_id' => $request->requesting_provider_id,
                    'attending_provider_id' => $bedData['attending_provider_id'] ?? $request->requesting_provider_id,
                    'priority' => $request->priority,
                    'bed_id' => $bed->id,
                ], $user);
            }

            $episode = IcuEpisode::create([
                'company_id' => $request->company_id,
                'branch_id' => $request->branch_id,
                'episode_number' => $this->numbers->generateEpisodeNumber($request->company_id, $request->branch_id),
                'patient_id' => $request->patient_id,
                'encounter_id' => $request->encounter_id,
                'admission_id' => $admission->id,
                'admission_request_id' => $request->id,
                'icu_unit_id' => $bed->room?->ward_id,
                'icu_bed_id' => $bed->id,
                'admission_reason' => $request->admission_reason,
                'primary_diagnosis_id' => $request->primary_diagnosis_id,
                'admitting_provider_id' => $request->requesting_provider_id,
                'attending_provider_id' => $bedData['attending_provider_id'] ?? $request->requesting_provider_id,
                'priority' => $request->priority,
                'status' => IcuEpisode::STATUS_ADMITTED,
                'admitted_at' => now(),
                'created_by' => $user->id,
            ]);

            IcuEpisodeEvent::create([
                'company_id' => $episode->company_id,
                'branch_id' => $episode->branch_id,
                'episode_id' => $episode->id,
                'event_type' => IcuEpisodeEvent::TYPE_ADMITTED,
                'to_status' => IcuEpisode::STATUS_ADMITTED,
                'to_bed_id' => $bed->id,
                'created_by' => $user->id,
            ]);

            event(new ICUPatientAdmitted($episode->fresh()));

            return $episode->fresh();
        });
    }
}
