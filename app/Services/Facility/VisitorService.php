<?php

namespace App\Services\Facility;

use App\Models\Facility\FacGateLog;
use App\Models\Facility\FacVisitorBlock;
use App\Models\Facility\FacVisitorPass;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Visitors and attendants. A pass belongs to an admitted patient; visitors come in visiting hours and only a few at once; one attendant stays with a patient; blocked people are refused; passes end when the
 * patient goes home. The gate log records every entry, exit and refusal and is never changed.
 */
class VisitorService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly FacilityPolicy $policy, private readonly AuditLogger $audit) {}

    /** @param array<string,mixed> $d kind, visitor_name, phone, relation, id_type, id_number, branch_id */
    public function issue(int $organizationId, int $admissionId, array $d, User $by): FacVisitorPass
    {
        $admission = DB::table('ipd_admissions')->where('organization_id', $organizationId)->where('id', $admissionId)->first(['id', 'patient_id', 'branch_id', 'status', 'admitted_at', 'actual_discharge_date']);
        if (! $admission || $admission->status === 'cancelled' || CarbonImmutable::parse((string) $admission->admitted_at)->gt(now()) || ($admission->actual_discharge_date !== null && CarbonImmutable::parse((string) $admission->actual_discharge_date)->lte(now()))) {
            throw ValidationException::withMessages(['admission' => __('Passes are only for patients who are in hospital.')]);
        }
        $kind = $d['kind'] ?? 'visitor';
        $name = trim((string) ($d['visitor_name'] ?? ''));
        if (! in_array($kind, ['visitor', 'attendant'], true) || mb_strlen($name) < 3) {
            throw ValidationException::withMessages(['visitor_name' => __('Enter the visitor\'s name.')]);
        }
        if (! empty($d['id_type']) && ! in_array($d['id_type'], $this->policy->list('visitor.id_types'), true)) {
            throw ValidationException::withMessages(['id_type' => __('Choose the kind of identity paper.')]);
        }
        if ($kind === 'attendant' && (empty($d['id_number']) || mb_strlen(trim((string) $d['id_number'])) < 4)) {
            throw ValidationException::withMessages(['id_number' => __('An attendant must show an identity paper.')]);
        }
        if ($this->blocked($organizationId, $name, $d['phone'] ?? null)) {
            throw ValidationException::withMessages(['visitor_name' => __('This person may not be given a pass.')]);
        }
        if ($kind === 'attendant') {
            $active = FacVisitorPass::query()->where(['admission_id' => $admission->id, 'kind' => 'attendant', 'status' => 'active'])->count();
            if ($active >= $this->policy->int('visitor.max_attendants', 1)) {
                throw ValidationException::withMessages(['kind' => __('This patient already has the allowed number of attendants.')]);
            }
        }
        $until = $kind === 'attendant' ? now()->addDays(7) : now((string) config('facility.timezone', 'Asia/Dhaka'))->endOfDay()->utc(); // a visitor's pass ends with the local day
        $pass = FacVisitorPass::create(['organization_id' => $organizationId, 'branch_id' => $admission->branch_id, 'number' => $this->numbers->next('fac.pass', $organizationId, null, prefix: 'VP', format: '{PREFIX}-{YEAR}{MONTH}-{SEQ:5}', period: NumberSeriesService::PERIOD_MONTH),
            'kind' => $kind, 'visitor_name' => mb_substr($name, 0, 150), 'phone' => isset($d['phone']) ? mb_substr((string) $d['phone'], 0, 30) : null, 'relation' => isset($d['relation']) ? mb_substr((string) $d['relation'], 0, 60) : null,
            'id_type' => $d['id_type'] ?? null, 'id_number' => ! empty($d['id_number']) ? trim((string) $d['id_number']) : null, 'patient_id' => $admission->patient_id, 'admission_id' => $admission->id, 'valid_from' => now(), 'valid_until' => $until, 'status' => 'active', 'issued_by' => $by->id]);
        $this->audit->log('CREATE', FacVisitorPass::class, $pass->id, null, ['number' => $pass->number, 'kind' => $kind]);

        return $pass;
    }

    public function revoke(FacVisitorPass $pass, User $by, string $reason): FacVisitorPass
    {
        if ($pass->status !== 'active' || mb_strlen(trim($reason)) < 3) {
            throw ValidationException::withMessages(['reason' => __('Say why the pass is withdrawn.')]);
        }
        $pass->update(['status' => 'revoked', 'revoke_reason' => mb_substr($reason, 0, 200)]);
        $this->audit->log('UPDATE', FacVisitorPass::class, $pass->id, null, ['status' => 'revoked']);

        return $pass;
    }

    public function block(int $organizationId, string $name, ?string $phone, string $reason, User $by): FacVisitorBlock
    {
        if (mb_strlen(trim($name)) < 3 || mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['name' => __('Enter the name and why the person is not allowed.')]);
        }
        $b = FacVisitorBlock::create(['organization_id' => $organizationId, 'name' => mb_substr(trim($name), 0, 150), 'phone' => $phone ? mb_substr($phone, 0, 30) : null, 'reason' => mb_substr($reason, 0, 300), 'active' => true, 'created_by' => $by->id]);
        FacVisitorPass::query()->where('organization_id', $organizationId)->where('status', 'active')->where(fn ($q) => $q->whereRaw('lower(visitor_name) = ?', [mb_strtolower(trim($name))])->when($phone, fn ($w) => $w->orWhere('phone', $phone)))->update(['status' => 'revoked', 'revoke_reason' => 'Blocked']);
        $this->audit->log('CREATE', FacVisitorBlock::class, $b->id, null, ['name' => $b->name]);

        return $b;
    }

    public function unblock(FacVisitorBlock $b): FacVisitorBlock
    {
        $b->update(['active' => false]);

        return $b;
    }

    private function blocked(int $organizationId, string $name, ?string $phone): bool
    {
        return FacVisitorBlock::query()->where('organization_id', $organizationId)->where('active', true)->where(fn ($q) => $q->whereRaw('lower(name) = ?', [mb_strtolower(trim($name))])->when($phone, fn ($w) => $w->orWhere('phone', $phone)))->exists();
    }

    /** At the gate. A refusal is logged too and comes back as an exception message the guard can read. */
    public function enter(FacVisitorPass $pass, User $by, ?string $gate = null): FacGateLog
    {
        $why = $this->refusal($pass);
        if ($why !== null) {
            $this->log($pass, 'in', 'refused', $gate, $by, $why);
            throw ValidationException::withMessages(['pass' => $why]);
        }
        if ($this->inside($pass)) {
            throw ValidationException::withMessages(['pass' => __('This person is already inside.')]);
        }

        return $this->log($pass, 'in', 'allowed', $gate, $by);
    }

    public function leave(FacVisitorPass $pass, User $by, ?string $gate = null): FacGateLog
    {
        if (! $this->inside($pass)) {
            throw ValidationException::withMessages(['pass' => __('This person is not inside.')]);
        }

        return $this->log($pass, 'out', 'allowed', $gate, $by);
    }

    public function inside(FacVisitorPass $pass): bool
    {
        $last = FacGateLog::query()->where('pass_id', $pass->id)->where('outcome', 'allowed')->orderByDesc('id')->first();

        return $last !== null && $last->direction === 'in';
    }

    /** Why this pass would be refused right now, or null. */
    public function refusal(FacVisitorPass $pass): ?string
    {
        if ($pass->status !== 'active') {
            return __('This pass is :s.', ['s' => $pass->status]);
        }
        if ($pass->valid_until->lt(now()) || $pass->valid_from->gt(now())) {
            return __('This pass is not valid now.');
        }
        if ($this->blocked($pass->organization_id, $pass->visitor_name, $pass->phone)) {
            return __('This person is not allowed in.');
        }
        if ($pass->kind === 'visitor') {
            if (! $this->withinVisitingHours()) {
                return __('It is outside visiting hours.');
            }
            $inside = FacVisitorPass::query()->where(['admission_id' => $pass->admission_id, 'kind' => 'visitor'])->where('id', '!=', $pass->id)->get()->filter(fn ($p) => $this->inside($p))->count();
            if ($inside >= $this->policy->int('visitor.max_visitors_at_once', 2)) {
                return __('This patient already has :n visitors with them.', ['n' => $inside]);
            }
        }

        return null;
    }

    public function withinVisitingHours(?CarbonImmutable $at = null): bool
    {
        $now = ($at ?? CarbonImmutable::now())->setTimezone((string) config('facility.timezone', 'Asia/Dhaka'))->format('H:i');
        foreach ((array) $this->policy->get('visitor.hours', []) as [$from, $to]) {
            if ($now >= $from && $now <= $to) {
                return true;
            }
        }

        return false;
    }

    /** Passes of patients who went home, and passes past their time, end. Anyone still inside is logged out. @return int passes ended */
    public function expireEnded(int $organizationId): int
    {
        $n = 0;
        foreach (FacVisitorPass::query()->where('organization_id', $organizationId)->where('status', 'active')->get() as $pass) {
            $gone = DB::table('ipd_admissions')->where('id', $pass->admission_id)->where(fn ($q) => $q->whereNotNull('actual_discharge_date')->orWhereIn('status', ['discharged', 'closed']))->exists();
            if ($gone || $pass->valid_until->lt(now())) {
                $pass->update(['status' => 'expired']);
                $n++;
            }
        }

        return $n;
    }

    private function log(FacVisitorPass $pass, string $direction, string $outcome, ?string $gate, User $by, ?string $note = null): FacGateLog
    {
        return FacGateLog::create(['organization_id' => $pass->organization_id, 'branch_id' => $pass->branch_id, 'pass_id' => $pass->id, 'direction' => $direction, 'gate' => $gate ? mb_substr($gate, 0, 40) : null, 'outcome' => $outcome, 'note' => $note ? mb_substr($note, 0, 200) : null, 'recorded_by' => $by->id]);
    }

    public function maskedId(?string $id): string
    {
        $id = (string) $id;

        return $id === '' ? '—' : str_repeat('•', max(0, mb_strlen($id) - 4)).mb_substr($id, -4);
    }
}
