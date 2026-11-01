<?php

namespace App\Services\Quality;

use App\Models\Patient;
use App\Models\Quality\QaHandHygieneAudit;
use App\Models\Quality\QaInfectionCase;
use App\Models\Quality\QaIsolation;
use App\Models\Quality\QaOutbreak;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Infection control: hospital-acquired infection cases, isolation precautions (a flag other screens can ask about), hand-hygiene audits and outbreak detection.
 * Opening a case never changes the patient's clinical record; the case points at the admission.
 */
class InfectionService
{
    public function __construct(private readonly QualityPolicy $policy, private readonly QualityAlerts $alerts, private readonly AuditLogger $audit) {}

    /** @param array<string,mixed> $d patient_id, admission_id, ward_id, hai_type, organism, device, onset_on, note, branch_id */
    public function reportCase(int $organizationId, array $d, User $by): QaInfectionCase
    {
        $patient = Patient::query()->where('organization_id', $organizationId)->find($d['patient_id'] ?? 0) ?? throw ValidationException::withMessages(['patient_id' => __('Choose the patient.')]);
        if (! in_array($d['hai_type'] ?? '', $this->policy->list('infection.hai_types'), true)) {
            throw ValidationException::withMessages(['hai_type' => __('Choose the type of infection.')]);
        }
        $onset = $d['onset_on'] ?? today()->toDateString();
        if (strtotime((string) $onset) > strtotime(today()->toDateString())) {
            throw ValidationException::withMessages(['onset_on' => __('The date cannot be in the future.')]);
        }
        $admission = null;
        $day = null;
        if (! empty($d['admission_id'])) {
            $admission = DB::table('ipd_admissions')->where('organization_id', $organizationId)->where('patient_id', $patient->id)->where('id', $d['admission_id'])->first();
            if (! $admission) {
                throw ValidationException::withMessages(['admission_id' => __('That admission is not this patient\'s.')]);
            }
            $day = max(1, (int) CarbonImmutable::parse((string) ($admission->admitted_at ?? $admission->created_at))->startOfDay()->diffInDays(CarbonImmutable::parse((string) $onset)->startOfDay()) + 1);
        }
        $case = QaInfectionCase::create(['organization_id' => $organizationId, 'branch_id' => $d['branch_id'] ?? $admission->branch_id ?? null, 'patient_id' => $patient->id, 'admission_id' => $admission->id ?? null,
            'ward_id' => $d['ward_id'] ?? null, 'hai_type' => $d['hai_type'], 'organism' => $this->clip($d['organism'] ?? null, 80), 'device' => $this->clip($d['device'] ?? null, 30), 'onset_on' => $onset,
            'hospital_day' => $day, 'status' => 'suspected', 'note' => $this->clip($d['note'] ?? null, 500), 'reported_by' => $by->id]);
        $this->audit->log('CREATE', QaInfectionCase::class, $case->id, null, ['type' => $case->hai_type, 'patient' => $patient->id]);
        $this->detectOutbreak($organizationId, $case);

        return $case;
    }

    /** The infection-control officer confirms or rules out a suspected case. */
    public function decide(QaInfectionCase $case, User $by, bool $confirm, ?string $note = null): QaInfectionCase
    {
        if ($case->status !== 'suspected') {
            throw ValidationException::withMessages(['status' => __('This case has been decided.')]);
        }
        $case->update(['status' => $confirm ? 'confirmed' : 'ruled_out', 'decided_by' => $by->id, 'decided_at' => now(), 'note' => $note ? mb_substr($note, 0, 500) : $case->note]);
        $this->audit->log('UPDATE', QaInfectionCase::class, $case->id, null, ['status' => $case->status]);
        if ($confirm) {
            $this->detectOutbreak($case->organization_id, $case);
        }

        return $case;
    }

    /** True when the case counts as acquired in hospital (onset on or after the set hospital day). */
    public function hospitalAcquired(QaInfectionCase $case): bool
    {
        return $case->hospital_day !== null && $case->hospital_day >= $this->policy->int('infection.hospital_acquired_from_day', 3);
    }

    public function isolate(int $organizationId, Patient $patient, string $precaution, string $reason, User $by, ?int $admissionId = null): QaIsolation
    {
        if (! in_array($precaution, $this->policy->list('infection.precautions'), true)) {
            throw ValidationException::withMessages(['precaution' => __('Choose the kind of precaution.')]);
        }
        if (mb_strlen(trim($reason)) < 3) {
            throw ValidationException::withMessages(['reason' => __('Say why.')]);
        }
        if ($patient->organization_id !== $organizationId) {
            abort(404);
        }
        if (QaIsolation::query()->where(['organization_id' => $organizationId, 'patient_id' => $patient->id, 'precaution' => $precaution])->whereNull('ended_at')->exists()) {
            throw ValidationException::withMessages(['precaution' => __('This precaution is already in place.')]);
        }
        $i = QaIsolation::create(['organization_id' => $organizationId, 'patient_id' => $patient->id, 'admission_id' => $admissionId, 'precaution' => $precaution, 'reason' => mb_substr(trim($reason), 0, 200), 'started_at' => now(), 'ordered_by' => $by->id]);
        $this->audit->log('CREATE', QaIsolation::class, $i->id, null, ['precaution' => $precaution]);

        return $i;
    }

    public function endIsolation(QaIsolation $isolation, User $by): QaIsolation
    {
        if ($isolation->ended_at !== null) {
            throw ValidationException::withMessages(['status' => __('Already ended.')]);
        }
        $isolation->update(['ended_at' => now(), 'ended_by' => $by->id]);
        $this->audit->log('UPDATE', QaIsolation::class, $isolation->id, null, ['ended' => true]);

        return $isolation;
    }

    /** Precautions in force for a patient: other screens ask this, they do not read the table. @return list<string> */
    public function activePrecautions(int $organizationId, int $patientId): array
    {
        return QaIsolation::query()->where(['organization_id' => $organizationId, 'patient_id' => $patientId])->whereNull('ended_at')->pluck('precaution')->all();
    }

    /** One audit round: moments observed and moments where hands were cleaned correctly, by role. */
    public function auditHandHygiene(int $organizationId, array $d, User $by): QaHandHygieneAudit
    {
        $obs = (int) ($d['moments_observed'] ?? 0);
        $ok = (int) ($d['moments_compliant'] ?? 0);
        if ($obs < 1 || $ok < 0 || $ok > $obs) {
            throw ValidationException::withMessages(['moments_compliant' => __('Compliant moments cannot be more than the moments observed.')]);
        }
        if (! in_array($d['staff_role'] ?? '', ['doctor', 'nurse', 'support', 'other'], true)) {
            throw ValidationException::withMessages(['staff_role' => __('Choose who was observed.')]);
        }

        return QaHandHygieneAudit::create(['organization_id' => $organizationId, 'branch_id' => $d['branch_id'] ?? null, 'ward_id' => $d['ward_id'] ?? null, 'ward_name' => $this->clip($d['ward_name'] ?? null, 80),
            'audited_on' => $d['audited_on'] ?? today()->toDateString(), 'staff_role' => $d['staff_role'], 'moments_observed' => $obs, 'moments_compliant' => $ok, 'auditor_id' => $by->id, 'note' => $this->clip($d['note'] ?? null, 300)]);
    }

    /** Compliance in percent over a period, or null when nothing was observed. */
    public function handHygiene(int $organizationId, string $from, string $to): ?float
    {
        $observed = (int) QaHandHygieneAudit::query()->where('organization_id', $organizationId)->whereBetween('audited_on', [$from, $to])->sum('moments_observed');
        $correct = (int) QaHandHygieneAudit::query()->where('organization_id', $organizationId)->whereBetween('audited_on', [$from, $to])->sum('moments_compliant');

        return $observed > 0 ? round(100 * $correct / $observed, 1) : null;
    }

    /**
     * Open an outbreak when enough cases of the same type (or organism) arise on one ward within the window; add to an open one instead of opening a second.
     */
    public function detectOutbreak(int $organizationId, QaInfectionCase $case): ?QaOutbreak
    {
        if ($case->status === 'ruled_out' || $case->ward_id === null) {
            return null;
        }
        $window = $this->policy->int('infection.outbreak_window_days', 14);
        $cases = QaInfectionCase::query()->where('organization_id', $organizationId)->where('ward_id', $case->ward_id)->where('status', '!=', 'ruled_out')->where('onset_on', '>=', $case->onset_on->copy()->subDays($window)->toDateString())
            ->where(fn ($q) => $q->where('hai_type', $case->hai_type)->when($case->organism, fn ($w) => $w->orWhere('organism', $case->organism)))->count();
        if ($cases < $this->policy->int('infection.outbreak_cases', 3)) {
            return null;
        }
        $open = QaOutbreak::query()->where(['organization_id' => $organizationId, 'ward_id' => $case->ward_id, 'hai_type' => $case->hai_type])->where('status', '!=', 'closed')->first();
        if ($open) {
            $open->update(['case_count' => max($open->case_count, $cases)]);

            return $open;
        }
        $outbreak = QaOutbreak::create(['organization_id' => $organizationId, 'branch_id' => $case->branch_id, 'ward_id' => $case->ward_id, 'organism' => $case->organism, 'hai_type' => $case->hai_type, 'case_count' => $cases, 'opened_on' => today()->toDateString(), 'status' => 'open']);
        $this->alerts->raise($organizationId, 'critical', __('Possible outbreak: :n cases of :t on the same ward within :d days.', ['n' => $cases, 't' => strtoupper($case->hai_type), 'd' => $window]),
            QaOutbreak::class, $outbreak->id, 'qa-outbreak-'.$outbreak->id, 'quality.infection.manage', true, $case->branch_id);
        $this->audit->log('CREATE', QaOutbreak::class, $outbreak->id, null, ['cases' => $cases]);

        return $outbreak;
    }

    public function closeOutbreak(QaOutbreak $outbreak, User $by, string $note, bool $contained = false): QaOutbreak
    {
        if ($outbreak->status === 'closed') {
            throw ValidationException::withMessages(['status' => __('Already closed.')]);
        }
        if (mb_strlen(trim($note)) < 5) {
            throw ValidationException::withMessages(['note' => __('Say what was done.')]);
        }
        $outbreak->update(['status' => $contained ? 'contained' : 'closed', 'closed_on' => $contained ? null : today()->toDateString(), 'note' => mb_substr($note, 0, 500), 'closed_by' => $contained ? null : $by->id]);
        $this->audit->log('UPDATE', QaOutbreak::class, $outbreak->id, null, ['status' => $outbreak->status]);

        return $outbreak;
    }

    private function clip(mixed $v, int $n): ?string
    {
        $v = trim(strip_tags((string) $v));

        return $v === '' ? null : mb_substr($v, 0, $n);
    }
}
