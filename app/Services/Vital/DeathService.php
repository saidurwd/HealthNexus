<?php

namespace App\Services\Vital;

use App\Models\Patient;
use App\Models\User;
use App\Models\Vital\VrDeathCertification;
use App\Models\Vital\VrMedicolegalCase;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Death certification. A doctor writes the medical certificate of cause of death (the WHO chain: the direct cause, and each line due to the one below it) and certifies it; a second person checks who the family member is and
 * issues it. A death that is an accident, a suicide, a killing or of unknown manner is a police matter: a medico-legal case is opened with it and the certificate is not issued to the family until that case is closed. A certified or
 * issued certificate is never edited; it is cancelled with a reason and written again.
 */
class DeathService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly VitalPolicy $policy, private readonly MedicoLegalService $medicoLegal, private readonly AuditLogger $audit) {}

    /** Deaths on record (a death date on the patient, or a mortuary case) that no live certification covers. @return Collection<int, object{patient_id:int,died_at:string}> */
    public function awaiting(int $organizationId): Collection
    {
        $covered = DB::table('vr_death_certifications')->where('organization_id', $organizationId)->where('status', '!=', 'cancelled')->select('patient_id');
        $deaths = DB::table('patients')->where('organization_id', $organizationId)->whereNull('deleted_at')->whereNotNull('deceased_at')->whereNotIn('id', $covered)->select(['id as patient_id', 'deceased_at as died_at']);
        $mortuary = DB::table('fac_mortuary_cases')->where('organization_id', $organizationId)->whereNotIn('patient_id', $covered)->whereNotIn('patient_id', DB::table('patients')->where('organization_id', $organizationId)->whereNotNull('deceased_at')->select('id'))->select(['patient_id', 'died_at']);

        return $deaths->union($mortuary)->orderBy('died_at')->get();
    }

    /** @param  array<string,mixed>  $d */
    public function draft(Patient $patient, array $d, User $by): VrDeathCertification
    {
        if (VrDeathCertification::query()->where(['organization_id' => $patient->organization_id, 'patient_id' => $patient->id])->where('status', '!=', 'cancelled')->exists()) {
            throw ValidationException::withMessages(['patient_id' => __('This patient already has a death certificate. Cancel it first if it has to be written again.')]);
        }
        $d = $this->clean($patient, $d);

        return DB::transaction(function () use ($patient, $d, $by) {
            $mortuary = DB::table('fac_mortuary_cases')->where('organization_id', $patient->organization_id)->where('patient_id', $patient->id)->first(['id', 'source_type', 'source_id']);
            $cert = VrDeathCertification::create($d + [
                'organization_id' => $patient->organization_id, 'patient_id' => $patient->id, 'status' => 'draft', 'created_by' => $by->id,
                'mortuary_case_id' => $mortuary->id ?? null, 'source_type' => $mortuary->source_type ?? null, 'source_id' => $mortuary->source_id ?? null,
                'number' => $this->numbers->next('vital.death', (int) $patient->organization_id, prefix: 'DC', format: '{PREFIX}-{YEAR}-{SEQ:5}', period: NumberSeriesService::PERIOD_YEAR, userId: $by->id),
            ]);
            if ($cert->medico_legal) {
                $existing = VrMedicolegalCase::query()->where(['organization_id' => $patient->organization_id, 'patient_id' => $patient->id])->where('status', '!=', 'closed')->first();
                $case = $existing ?? $this->medicoLegal->register($patient, ['kind' => 'suspicious_death', 'brought_at' => min($cert->died_at->toDateTimeString(), now()->toDateTimeString()), 'description' => 'Opened from the death certificate: the manner of death is "'.str_replace('_', ' ', $cert->manner).'".'], $by);
                $cert->update(['medicolegal_case_id' => $case->id]);
            }
            $this->audit->log('CREATE', VrDeathCertification::class, $cert->id, null, ['number' => $cert->number, 'manner' => $cert->manner]);

            return $cert;
        });
    }

    /** The doctor signs the cause of death. */
    public function certify(VrDeathCertification $c, User $doctor, ?string $registrationNo = null): VrDeathCertification
    {
        if ($c->status !== 'draft') {
            throw ValidationException::withMessages(['status' => __('Only a draft can be certified.')]);
        }
        $this->assertChain($c->only(['cause_a', 'cause_b', 'cause_c', 'cause_d']));
        $c->update(['status' => 'certified', 'certifier_id' => $doctor->id, 'certifier_name' => $doctor->name, 'certifier_reg_no' => $registrationNo !== null ? mb_substr(trim($registrationNo), 0, 40) : null, 'certified_at' => now()]);
        // The patient's record carries the death if it did not already.
        Patient::query()->whereKey($c->patient_id)->whereNull('deceased_at')->update(['deceased_at' => $c->died_at]);
        $this->audit->log('UPDATE', VrDeathCertification::class, $c->id, ['status' => 'draft'], ['status' => 'certified', 'certifier_id' => $doctor->id]);

        return $c;
    }

    /** A second person checks who the family member is and gives them the certificate. */
    public function issue(VrDeathCertification $c, User $verifier, string $issuedTo, string $relation, string $idType, string $idNumber): VrDeathCertification
    {
        return DB::transaction(function () use ($c, $verifier, $issuedTo, $relation, $idType, $idNumber) {
            $c = VrDeathCertification::query()->lockForUpdate()->findOrFail($c->id);
            if ($c->status !== 'certified') {
                throw ValidationException::withMessages(['status' => __('Only a certified certificate can be issued.')]);
            }
            if ($c->certifier_id === $verifier->id) {
                throw ValidationException::withMessages(['status' => __('A second person must check the family member and issue it.')]);
            }
            if ($c->medicolegal_case_id && ! VrMedicolegalCase::query()->whereKey($c->medicolegal_case_id)->where('status', 'closed')->exists()) {
                throw ValidationException::withMessages(['status' => __('The police case is not closed yet, so the certificate cannot be given to the family.')]);
            }
            if (trim($issuedTo) === '' || trim($relation) === '' || trim($idNumber) === '' || ! in_array($idType, $this->policy->list('id_types'), true)) {
                throw ValidationException::withMessages(['issued_to' => __('Give the name, relation and identity papers of the person receiving it.')]);
            }
            $c->update(['status' => 'issued', 'verified_by' => $verifier->id, 'issued_by' => $verifier->id, 'issued_at' => now(), 'issued_to' => mb_substr(trim($issuedTo), 0, 150), 'issued_relation' => mb_substr(trim($relation), 0, 60), 'issued_id_type' => $idType, 'issued_id_number' => trim($idNumber)]);
            $this->audit->log('UPDATE', VrDeathCertification::class, $c->id, ['status' => 'certified'], ['status' => 'issued', 'issued_to' => $c->issued_to]);

            return $c;
        });
    }

    public function cancel(VrDeathCertification $c, string $reason, User $by): VrDeathCertification
    {
        $reason = trim($reason);
        if ($c->status === 'cancelled' || mb_strlen($reason) < 5) {
            throw ValidationException::withMessages(['cancel_reason' => __('Give the reason, in a few words.')]);
        }
        $before = $c->status;
        $c->update(['status' => 'cancelled', 'cancel_reason' => mb_substr($reason, 0, 255), 'cancelled_by' => $by->id, 'cancelled_at' => now()]);
        $this->audit->log('UPDATE', VrDeathCertification::class, $c->id, ['status' => $before], ['status' => 'cancelled', 'cancel_reason' => $c->cancel_reason]);

        return $c;
    }

    /**
     * Part I is a chain: a line may be given only if the line above it is.
     *
     * @param  array<string,mixed>  $lines  cause_a .. cause_d
     */
    public function assertChain(array $lines): void
    {
        $filled = array_map(fn ($k) => trim((string) ($lines[$k] ?? '')) !== '', ['cause_a', 'cause_b', 'cause_c', 'cause_d']);
        if (! $filled[0]) {
            throw ValidationException::withMessages(['cause_a' => __('Give the direct cause of death.')]);
        }
        foreach ([1, 2, 3] as $i) {
            if ($filled[$i] && ! $filled[$i - 1]) {
                throw ValidationException::withMessages(['cause_'.chr(97 + $i) => __('A cause can be given only if the line above it is filled.')]);
            }
        }
    }

    /**
     * @param  array<string,mixed>  $d
     * @return array<string,mixed>
     */
    private function clean(Patient $patient, array $d): array
    {
        $died = ! empty($d['died_at']) ? CarbonImmutable::parse((string) $d['died_at']) : null;
        if ($died === null || $died->isFuture()) {
            throw ValidationException::withMessages(['died_at' => __('Give the date and time of death; it cannot be in the future.')]);
        }
        if (! in_array($d['manner'] ?? '', $this->policy->list('manners'), true)) {
            throw ValidationException::withMessages(['manner' => __('Choose the manner of death.')]);
        }
        $this->assertChain($d);
        $keep = ['branch_id', 'place_of_death', 'ward_or_area', 'manner', 'cause_a', 'interval_a', 'cause_b', 'interval_b', 'cause_c', 'interval_c', 'cause_d', 'interval_d', 'contributing', 'underlying_icd10', 'autopsy_done', 'pregnancy_status'];
        $out = array_intersect_key($d, array_flip($keep));
        $out['died_at'] = $died;
        $out['medico_legal'] = in_array($d['manner'], $this->policy->list('police_manners'), true);
        $out['autopsy_done'] = (bool) ($d['autopsy_done'] ?? false);
        $out['underlying_icd10'] = isset($out['underlying_icd10']) && $out['underlying_icd10'] !== '' ? strtoupper(mb_substr(trim((string) $out['underlying_icd10']), 0, 12)) : null;
        if (in_array(strtolower((string) $patient->sex), ['m', 'male'], true)) {
            $out['pregnancy_status'] = null;
        }

        return array_filter($out, fn ($v) => $v !== '') + ['place_of_death' => 'this_hospital'];
    }
}
