<?php

namespace App\Services\Interop;

use App\Models\Integration\IntegrationPartner;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Patients\PatientService;
use Illuminate\Support\Facades\DB;

/**
 * The one door by which a patient described by an outside system (FHIR Patient, HL7 PID) enters or is updated. It finds an existing patient first — never duplicates — and then goes through
 * `PatientService` like every other registration (guideline: nothing writes `patients` directly).
 *
 * @phpstan-type Intake array{first_name?:string,middle_name?:?string,last_name?:string,date_of_birth?:?string,sex?:?string,phone?:?string,email?:?string,national_identifier?:?string,address?:?string,city?:?string,external_id?:?string,mrn?:?string}
 */
class PatientIntakeService
{
    public function __construct(private readonly PatientService $patients, private readonly InteropSystemUser $system, private readonly IdMapper $ids, private readonly AuditLogger $audit) {}

    /** @param Intake $in */
    public function find(int $organizationId, ?IntegrationPartner $partner, array $in): ?Patient
    {
        if ($partner && ! empty($in['external_id'])) {
            $local = $this->ids->localFor($partner->code, 'Patient', (string) $in['external_id'], $organizationId);
            if ($local && ($p = Patient::query()->where('organization_id', $organizationId)->find($local))) {
                return $p;
            }
        }
        if (! empty($in['mrn']) && ($p = Patient::query()->where(['organization_id' => $organizationId, 'enterprise_patient_no' => $in['mrn']])->first())) {
            return $p;
        }
        if (! empty($in['national_identifier']) && ($p = Patient::query()->where(['organization_id' => $organizationId, 'national_identifier' => $in['national_identifier']])->first())) {
            return $p;
        }
        if (! empty($in['first_name']) && ! empty($in['last_name']) && ! empty($in['date_of_birth'])) {
            $candidates = Patient::query()->where('organization_id', $organizationId)->whereRaw('lower(first_name) = ?', [mb_strtolower($in['first_name'])])->whereRaw('lower(last_name) = ?', [mb_strtolower($in['last_name'])])
                ->whereDate('date_of_birth', $in['date_of_birth'])->whereNull('merged_into_patient_id')->get();
            if ($candidates->count() === 1) {
                return $candidates->first();
            }
        }

        return null;
    }

    /**
     * @param  Intake  $in
     * @return array{0:Patient,1:bool} the patient and whether it was newly made
     */
    public function upsert(int $organizationId, ?IntegrationPartner $partner, array $in, bool $allowCreate = true): array
    {
        return DB::transaction(function () use ($organizationId, $partner, $in, $allowCreate) {
            $existing = $this->find($organizationId, $partner, $in);
            $fields = array_filter([
                'first_name' => $in['first_name'] ?? null, 'middle_name' => $in['middle_name'] ?? null, 'last_name' => $in['last_name'] ?? null, 'date_of_birth' => $in['date_of_birth'] ?? null, 'sex' => $in['sex'] ?? null,
                'phone' => $in['phone'] ?? null, 'email' => $in['email'] ?? null, 'national_identifier' => $in['national_identifier'] ?? null, 'address' => $in['address'] ?? null, 'city' => $in['city'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            if ($existing) {
                $changed = array_filter($fields, fn ($v, $k) => (string) ($existing->{$k} instanceof \DateTimeInterface ? $existing->{$k}->format('Y-m-d') : $existing->{$k}) !== (string) $v, ARRAY_FILTER_USE_BOTH);
                $changed !== [] && $this->patients->updatePatient($existing, $changed);
                $this->link($partner, $existing, $in);
                $this->audit->log('UPDATE', Patient::class, $existing->id, null, ['via' => 'integration', 'partner' => $partner?->code, 'fields' => array_keys($changed)]);

                return [$existing->refresh(), false];
            }
            if (! $allowCreate || empty($fields['first_name']) || empty($fields['last_name'])) {
                throw new InteropException('The patient could not be matched and there is not enough to register one (first and last name are needed).', 422, 'required');
            }
            $patient = $this->patients->createPatient($fields + ['organization_id' => $organizationId, 'status' => 'active'], $this->system->for($organizationId));
            $this->link($partner, $patient, $in);
            $this->audit->log('CREATE', Patient::class, $patient->id, null, ['via' => 'integration', 'partner' => $partner?->code]);

            return [$patient, true];
        });
    }

    /** @param Intake $in */
    private function link(?IntegrationPartner $partner, Patient $patient, array $in): void
    {
        if ($partner && ! empty($in['external_id'])) {
            $this->ids->link($partner->code, 'Patient', $patient->id, (string) $in['external_id'], $patient->organization_id);
        }
    }
}
