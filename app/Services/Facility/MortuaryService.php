<?php

namespace App\Services\Facility;

use App\Models\Facility\FacMortuaryCase;
use App\Models\Facility\FacMortuaryUnit;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The mortuary. Each death gets one case (found from the emergency department and the wards, once). Two staff check identity when the body is received; a unit holds only what its capacity allows;
 * release needs the family member's identity papers and a second person's authorisation, and a police case is held until it is cleared.
 */
class MortuaryService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly FacilityPolicy $policy, private readonly AuditLogger $audit) {}

    public function unit(int $organizationId, string $code, string $name, int $capacity): FacMortuaryUnit
    {
        $code = strtoupper(trim($code));
        if ($code === '' || mb_strlen(trim($name)) < 2 || $capacity < 1 || $capacity > 100) {
            throw ValidationException::withMessages(['capacity' => __('Give the unit a code, a name and a capacity.')]);
        }

        return FacMortuaryUnit::query()->updateOrCreate(['organization_id' => $organizationId, 'code' => $code], ['name' => mb_substr(trim($name), 0, 80), 'capacity' => $capacity, 'active' => true]);
    }

    /** Open a case for every death not yet seen. @return int new cases */
    public function sweep(int $organizationId): int
    {
        $n = 0;
        $ed = DB::table('ed_visits as v')->join('patients as p', 'p.id', '=', 'v.patient_id')->where('v.organization_id', $organizationId)->where('v.disposition_type', 'death')->select('v.id', 'v.patient_id', 'v.branch_id', 'v.dispositioned_at', 'p.deceased_at')->orderBy('v.id')->get();
        foreach ($ed as $r) {
            $n += $this->open($organizationId, 'ed_visit', (int) $r->id, (int) $r->patient_id, $r->branch_id, $r->deceased_at ?? $r->dispositioned_at);
        }
        $ward = DB::table('ipd_admissions as a')->join('patients as p', 'p.id', '=', 'a.patient_id')->where('a.organization_id', $organizationId)
            ->where(fn ($q) => $q->where('a.status', 'deceased')->orWhere(fn ($w) => $w->whereNotNull('p.deceased_at')->whereColumn('p.deceased_at', '>=', 'a.admitted_at')->where(fn ($d) => $d->whereNull('a.actual_discharge_date')->orWhereRaw('date(p.deceased_at) <= date(a.actual_discharge_date)'))))
            ->select('a.id', 'a.patient_id', 'a.branch_id', 'a.updated_at', 'p.deceased_at')->orderBy('a.id')->get();
        foreach ($ward as $r) {
            $n += $this->open($organizationId, 'ipd_admission', (int) $r->id, (int) $r->patient_id, $r->branch_id, $r->deceased_at ?? $r->updated_at);
        }

        return $n;
    }

    private function open(int $org, string $type, int $id, int $patientId, mixed $branchId, mixed $diedAt): int
    {
        $died = CarbonImmutable::parse((string) $diedAt, 'UTC');
        if ($died->isAfter(now()) || FacMortuaryCase::query()->where(['organization_id' => $org, 'source_type' => $type, 'source_id' => $id])->exists()) {
            return 0;
        }
        // one person, one case: a patient who died in the emergency department and was counted on the ward too is not opened twice
        if (FacMortuaryCase::query()->where(['organization_id' => $org, 'patient_id' => $patientId])->exists()) {
            return 0;
        }
        $case = FacMortuaryCase::create(['organization_id' => $org, 'branch_id' => $branchId, 'number' => $this->numbers->next('fac.mortuary', $org, null, prefix: 'MC', format: '{PREFIX}-{YEAR}-{SEQ:5}', period: NumberSeriesService::PERIOD_YEAR),
            'patient_id' => $patientId, 'source_type' => $type, 'source_id' => $id, 'died_at' => $died, 'status' => 'pending']);
        $this->audit->log('CREATE', FacMortuaryCase::class, $case->id, null, ['number' => $case->number]);

        return 1;
    }

    /** The body arrives: two different staff check the identity band against the records. */
    public function receive(FacMortuaryCase $case, User $by, User $verifier, string $tag, FacMortuaryUnit $unit, bool $policeCase = false): FacMortuaryCase
    {
        if ($case->status !== 'pending') {
            throw ValidationException::withMessages(['status' => __('This body has already been received.')]);
        }
        if ($by->id === $verifier->id) {
            throw ValidationException::withMessages(['verified_by' => __('A second person must check the identity.')]);
        }
        if (mb_strlen(trim($tag)) < 2) {
            throw ValidationException::withMessages(['tag_no' => __('Enter the identity tag number.')]);
        }
        if ($unit->organization_id !== $case->organization_id || ! $unit->active) {
            throw ValidationException::withMessages(['unit_id' => __('Choose a storage unit.')]);
        }

        return DB::transaction(function () use ($case, $by, $verifier, $tag, $unit, $policeCase) {
            $u = FacMortuaryUnit::query()->lockForUpdate()->findOrFail($unit->id);
            $held = FacMortuaryCase::query()->where('unit_id', $u->id)->whereIn('status', ['received', 'stored', 'release_pending'])->count();
            if ($held >= $u->capacity) {
                throw ValidationException::withMessages(['unit_id' => __(':u is full.', ['u' => $u->name])]);
            }
            $case->update(['status' => 'stored', 'received_at' => now(), 'received_by' => $by->id, 'verified_by' => $verifier->id, 'tag_no' => mb_substr(trim($tag), 0, 30), 'unit_id' => $u->id, 'police_case' => $policeCase, 'police_cleared' => ! $policeCase]);
            $this->audit->log('UPDATE', FacMortuaryCase::class, $case->id, null, ['received' => true, 'unit' => $u->code]);

            return $case->refresh();
        });
    }

    public function clearPolice(FacMortuaryCase $case, User $by): FacMortuaryCase
    {
        if (! $case->police_case || $case->police_cleared) {
            throw ValidationException::withMessages(['status' => __('Nothing to clear.')]);
        }
        $case->update(['police_cleared' => true]);
        $this->audit->log('UPDATE', FacMortuaryCase::class, $case->id, null, ['police_cleared' => true]);

        return $case;
    }

    /** The first step: who is taking the body and what papers they showed. */
    public function requestRelease(FacMortuaryCase $case, User $by, string $releasedTo, string $relation, string $idType, string $idNumber): FacMortuaryCase
    {
        if ($case->status !== 'stored') {
            throw ValidationException::withMessages(['status' => __('Only a stored body can be released.')]);
        }
        if ($case->police_case && ! $case->police_cleared) {
            throw ValidationException::withMessages(['status' => __('This is a police case: it cannot be released until the police clear it.')]);
        }
        if (mb_strlen(trim($releasedTo)) < 3 || mb_strlen(trim($relation)) < 2 || mb_strlen(trim($idNumber)) < 4 || ! in_array($idType, $this->policy->list('mortuary.id_types'), true)) {
            throw ValidationException::withMessages(['released_to' => __('Enter the name, relation and identity papers of the person.')]);
        }
        $case->update(['status' => 'release_pending', 'released_to' => mb_substr(trim($releasedTo), 0, 150), 'relation' => mb_substr(trim($relation), 0, 60), 'id_type' => $idType, 'id_number' => trim($idNumber), 'released_by' => $by->id]);
        $this->audit->log('UPDATE', FacMortuaryCase::class, $case->id, null, ['release_requested' => true]);

        return $case;
    }

    /** The second step: another person confirms and the body leaves. */
    public function authoriseRelease(FacMortuaryCase $case, User $by): FacMortuaryCase
    {
        if ($case->status !== 'release_pending') {
            throw ValidationException::withMessages(['status' => __('No release is waiting for authorisation.')]);
        }
        if ($this->policy->bool('mortuary.second_person_releases', true) && $case->released_by === $by->id) {
            throw ValidationException::withMessages(['status' => __('A second person must authorise the release.')]);
        }
        $case->update(['status' => 'released', 'authorised_by' => $by->id, 'released_at' => now()]);
        $this->audit->log('UPDATE', FacMortuaryCase::class, $case->id, null, ['released' => true]);

        return $case;
    }

    /** Cancel a pending release (the person did not come). */
    public function cancelRelease(FacMortuaryCase $case): FacMortuaryCase
    {
        if ($case->status !== 'release_pending') {
            throw ValidationException::withMessages(['status' => __('No release is waiting.')]);
        }
        $case->update(['status' => 'stored', 'released_to' => null, 'relation' => null, 'id_type' => null, 'id_number' => null, 'released_by' => null]);

        return $case;
    }

    /** Masked for screens: the last four characters only. */
    public function maskedId(?string $id): string
    {
        $id = (string) $id;

        return $id === '' ? '—' : str_repeat('•', max(0, mb_strlen($id) - 4)).mb_substr($id, -4);
    }

    /** @return array{free:int,used:int} */
    public function occupancy(int $organizationId): array
    {
        $cap = (int) FacMortuaryUnit::query()->where('organization_id', $organizationId)->where('active', true)->sum('capacity');
        $used = FacMortuaryCase::query()->where('organization_id', $organizationId)->whereIn('status', ['stored', 'release_pending'])->count();

        return ['free' => max(0, $cap - $used), 'used' => $used];
    }
}
