<?php

namespace App\Services\Patients;

use App\Events\PatientRegistered;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\PatientAddress;
use App\Models\PatientAlert;
use App\Models\PatientAllergy;
use App\Models\PatientBranchRegistration;
use App\Models\PatientConsent;
use App\Models\PatientContact;
use App\Models\PatientGuardian;
use App\Models\PatientHistory;
use App\Models\PatientPreference;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PatientService
{
    public function __construct(
        private PatientNumberGenerator $numberGenerator,
        private PatientTimelineService $timeline,
    ) {}

    public function createPatient(array $data, User $user, ?Branch $branch = null): Patient
    {
        return DB::transaction(function () use ($data, $user, $branch): Patient {
            $company = $user->companies()->findOrFail($data['company_id']);

            $data['company_id'] = $company->id;
            $data['enterprise_patient_no'] = $this->numberGenerator->generateEnterprisePatientNo($company);
            $data['registered_at'] = $data['registered_at'] ?? now();
            $data['registered_by'] = $user->id;

            $patient = Patient::create($data);

            if ($branch) {
                $this->registerPatientToBranch($patient, $branch, $user);
            }

            if (! empty($data['identifiers'])) {
                foreach ($data['identifiers'] as $identifierData) {
                    $identifierData['company_id'] = $company->id;
                    $patient->identifiers()->create($identifierData);
                }
            }

            if (! empty($data['contacts'])) {
                foreach ($data['contacts'] as $contactData) {
                    $contactData['company_id'] = $company->id;
                    $patient->contacts()->create($contactData);
                }
            }

            $this->timeline->record($patient, 'PATIENT_REGISTERED', 'Patient registered', $patient, $user);

            event(new PatientRegistered($patient));

            return $patient;
        });
    }

    public function registerPatientToBranch(Patient $patient, Branch $branch, User $user): PatientBranchRegistration
    {
        $company = $user->companies()->findOrFail($patient->company_id);

        $localPatientNo = $this->numberGenerator->generateLocalPatientNo($company, $branch);

        return PatientBranchRegistration::create([
            'company_id' => $company->id,
            'patient_id' => $patient->id,
            'branch_id' => $branch->id,
            'local_patient_no' => $localPatientNo,
            'registered_at' => now(),
            'status' => 'active',
        ]);
    }

    public function addIdentifier(Patient $patient, array $identifierData): void
    {
        $patient->identifiers()->create($identifierData);
    }

    public function addContact(Patient $patient, array $contactData): PatientContact
    {
        $contactData['company_id'] = $patient->company_id;

        return $patient->contacts()->create($contactData);
    }

    public function addAllergy(Patient $patient, array $allergyData): PatientAllergy
    {
        $allergyData['company_id'] = $patient->company_id;

        return $patient->allergies()->create($allergyData);
    }

    public function addAlert(Patient $patient, array $alertData): PatientAlert
    {
        $alertData['company_id'] = $patient->company_id;
        $alertData['created_by'] = $alertData['created_by'] ?? auth()->id();

        return $patient->alerts()->create($alertData);
    }

    public function resolveAlert(PatientAlert $alert, array $data): PatientAlert
    {
        $alert->update(array_merge($data, [
            'status' => 'resolved',
            'resolved_by' => $data['resolved_by'] ?? auth()->id(),
            'resolved_at' => now(),
        ]));

        return $alert;
    }

    public function addHistory(Patient $patient, array $historyData): PatientHistory
    {
        $historyData['company_id'] = $patient->company_id;
        $historyData['recorded_by'] = auth()->id();

        return $patient->histories()->create($historyData);
    }

    public function updatePatient(Patient $patient, array $data): Patient
    {
        $original = $patient->only(array_keys($data));

        $patient->update($data);

        $this->timeline->record($patient, 'PATIENT_UPDATED', 'Patient details updated', $patient, null, [
            'from' => $original,
            'to' => $patient->only(array_keys($data)),
        ]);

        return $patient;
    }

    public function addAddress(Patient $patient, array $addressData): PatientAddress
    {
        $addressData['company_id'] = $patient->company_id;

        if (! empty($addressData['is_primary'])) {
            $patient->addresses()->where('address_type', $addressData['address_type'])->update(['is_primary' => false]);
        }

        return $patient->addresses()->create($addressData);
    }

    public function addGuardian(Patient $patient, array $guardianData): PatientGuardian
    {
        $guardianData['company_id'] = $patient->company_id;

        return $patient->guardians()->create($guardianData);
    }

    public function setPreferences(Patient $patient, array $preferenceData): PatientPreference
    {
        $preferenceData['company_id'] = $patient->company_id;

        return $patient->preference()->updateOrCreate(['patient_id' => $patient->id], $preferenceData);
    }

    public function grantConsent(Patient $patient, string $consentType, User $grantedBy, ?string $notes = null): PatientConsent
    {
        return DB::transaction(function () use ($patient, $consentType, $grantedBy, $notes) {
            $patient->consents()->where('consent_type', $consentType)->where('status', 'active')
                ->update(['status' => 'withdrawn', 'withdrawn_by' => $grantedBy->id, 'withdrawn_at' => now()]);

            $version = $patient->consents()->where('consent_type', $consentType)->max('version') + 1;

            $consent = $patient->consents()->create([
                'company_id' => $patient->company_id,
                'consent_type' => $consentType,
                'version' => $version,
                'status' => 'active',
                'notes' => $notes,
                'granted_by' => $grantedBy->id,
                'granted_at' => now(),
            ]);

            $this->timeline->record($patient, 'CONSENT_GRANTED', "Consent granted: {$consentType}", $consent, $grantedBy);

            return $consent;
        });
    }

    public function withdrawConsent(PatientConsent $consent, User $withdrawnBy): PatientConsent
    {
        $consent->update([
            'status' => 'withdrawn',
            'withdrawn_by' => $withdrawnBy->id,
            'withdrawn_at' => now(),
        ]);

        $this->timeline->record($consent->patient, 'CONSENT_WITHDRAWN', "Consent withdrawn: {$consent->consent_type}", $consent, $withdrawnBy);

        return $consent;
    }

    public function detectDuplicates(int $companyId, string $firstName, string $lastName, ?string $phone = null, ?string $nationalId = null, ?string $email = null): Collection
    {
        return Patient::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($firstName, $lastName, $phone, $nationalId, $email) {
                $query->where('first_name', 'like', '%'.$firstName.'%')
                    ->where('last_name', 'like', '%'.$lastName.'%');

                if ($phone) {
                    $query->orWhere('phone', $phone);
                }

                if ($nationalId) {
                    $query->orWhere('national_identifier', $nationalId);
                }

                if ($email) {
                    $query->orWhere('email', $email);
                }
            })
            ->get();
    }

    public function mergePatients(Patient $masterPatient, Patient $duplicatePatient, ?User $actor = null): void
    {
        DB::transaction(function () use ($masterPatient, $duplicatePatient, $actor) {
            $this->transferRelations($masterPatient, $duplicatePatient);

            // Soft-deleted and flagged, never hard-deleted or name-mangled: merged_into_patient_id
            // preserves the audit trail and lets any surviving reference still resolve.
            $duplicatePatient->update([
                'status' => 'merged',
                'merged_into_patient_id' => $masterPatient->id,
            ]);

            $duplicatePatient->delete();

            $this->timeline->record($masterPatient, 'PATIENT_MERGED', "Merged patient #{$duplicatePatient->id} into this record", $duplicatePatient, $actor);
        });
    }

    protected function transferRelations(Patient $masterPatient, Patient $duplicatePatient): void
    {
        $duplicatePatient->identifiers()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->contacts()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->allergies()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->histories()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->alerts()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->documents()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->branchRegistrations()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->addresses()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->guardians()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->consents()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->amendments()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->timelineEvents()->update(['patient_id' => $masterPatient->id]);

        // Clinical/operational relations — a merge must not orphan a patient's care history.
        $duplicatePatient->encounters()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->appointments()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->vitalSigns()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->diagnoses()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->prescriptions()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->investigationOrders()->update(['patient_id' => $masterPatient->id]);

        if ($preference = $duplicatePatient->preference) {
            $masterPatient->preference
                ? $preference->delete()
                : $preference->update(['patient_id' => $masterPatient->id]);
        }

        if ($portalAccount = $duplicatePatient->portalAccount) {
            $masterPatient->portalAccount
                ? $portalAccount->delete()
                : $portalAccount->update(['patient_id' => $masterPatient->id]);
        }
    }

    /**
     * @deprecated Use PatientTimelineService::for() — this live-aggregates every related table
     * on each call. Kept only until any remaining callers migrate to the event-sourced timeline.
     */
    public function getTimeline(Patient $patient): Collection
    {
        $timeline = collect();

        $timeline->push($patient);

        $timeline = $timeline->merge($patient->identifiers);
        $timeline = $timeline->merge($patient->contacts);
        $timeline = $timeline->merge($patient->allergies);
        $timeline = $timeline->merge($patient->histories);
        $timeline = $timeline->merge($patient->documents);
        $timeline = $timeline->merge($patient->branchRegistrations);

        if ($patient->encounters()->exists()) {
            $timeline = $timeline->merge($patient->encounters);
        }

        return $timeline->sortBy('created_at')->reverse();
    }
}
