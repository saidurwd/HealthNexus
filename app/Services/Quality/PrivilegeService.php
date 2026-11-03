<?php

namespace App\Services\Quality;

use App\Models\Hr\HrEmployee;
use App\Models\Quality\QaPrivilege;
use App\Models\Quality\QaPrivilegeCatalog;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Clinical privileges: which procedures a clinician may perform here. A privilege is requested, then granted by someone other than the requester (and never the clinician themself),
 * only while the required licence in the HR credentials is valid; it expires, and it lapses if the licence does.
 */
class PrivilegeService
{
    public function __construct(private readonly QualityPolicy $policy, private readonly QualityAlerts $alerts, private readonly AuditLogger $audit) {}

    public function addToCatalog(int $organizationId, string $code, string $name, ?string $requiresCredential, ?int $validityMonths = null): QaPrivilegeCatalog
    {
        $code = strtoupper(trim($code));
        if ($code === '' || mb_strlen(trim($name)) < 3) {
            throw ValidationException::withMessages(['name' => __('Give the privilege a code and a name.')]);
        }

        return QaPrivilegeCatalog::query()->updateOrCreate(['organization_id' => $organizationId, 'code' => $code], ['name' => mb_substr(trim($name), 0, 150), 'requires_credential' => $requiresCredential ?: null,
            'validity_months' => $validityMonths ?: $this->policy->int('privilege.default_validity_months', 24), 'active' => true]);
    }

    public function request(int $organizationId, HrEmployee $employee, QaPrivilegeCatalog $item, User $by, ?string $note = null): QaPrivilege
    {
        if ($employee->organization_id !== $organizationId || $item->organization_id !== $organizationId) {
            abort(404);
        }
        if (QaPrivilege::query()->where(['organization_id' => $organizationId, 'employee_id' => $employee->id, 'catalog_id' => $item->id])->whereIn('status', ['requested', 'granted'])->exists()) {
            throw ValidationException::withMessages(['catalog_id' => __('This privilege is already requested or held.')]);
        }
        $p = QaPrivilege::create(['organization_id' => $organizationId, 'employee_id' => $employee->id, 'catalog_id' => $item->id, 'status' => 'requested', 'requested_by' => $by->id, 'note' => $note ? mb_substr($note, 0, 500) : null]);
        $this->audit->log('CREATE', QaPrivilege::class, $p->id, null, ['employee' => $employee->id, 'privilege' => $item->code]);

        return $p;
    }

    public function decide(QaPrivilege $p, User $by, bool $grant, ?string $note): QaPrivilege
    {
        if ($p->status !== 'requested') {
            throw ValidationException::withMessages(['status' => __('Nothing to decide.')]);
        }
        $employee = HrEmployee::query()->find($p->employee_id);
        if ($employee && $employee->user_id === $by->id) {
            throw ValidationException::withMessages(['status' => __('Nobody decides their own privileges.')]);
        }
        if ($this->policy->bool('privilege.second_person_decides', true) && $p->requested_by === $by->id) {
            throw ValidationException::withMessages(['status' => __('A second person must decide.')]);
        }
        if (! $grant) {
            if (mb_strlen(trim((string) $note)) < 5) {
                throw ValidationException::withMessages(['note' => __('Say why.')]);
            }
            $p->update(['status' => 'declined', 'decided_by' => $by->id, 'decided_at' => now(), 'note' => mb_substr((string) $note, 0, 500)]);

            return $p;
        }
        $item = $p->item;
        $until = today()->addMonths($item->validity_months);
        if ($item->requires_credential) {
            $credential = DB::table('hr_credentials')->where(['employee_id' => $p->employee_id, 'credential_type' => $item->requires_credential, 'status' => 'active'])
                ->where(fn ($q) => $q->whereNull('expires_on')->orWhere('expires_on', '>=', today()->toDateString()))->orderByDesc('expires_on')->first();
            if (! $credential) {
                throw ValidationException::withMessages(['status' => __('A valid :c licence is needed first.', ['c' => strtoupper($item->requires_credential)])]);
            }
            if ($credential->expires_on && $until->gt($credential->expires_on)) {
                $until = CarbonImmutable::parse($credential->expires_on);
            }
        }
        $p->update(['status' => 'granted', 'decided_by' => $by->id, 'decided_at' => now(), 'granted_on' => today()->toDateString(), 'expires_on' => $until->toDateString(), 'note' => $note ? mb_substr($note, 0, 500) : $p->note]);
        $this->audit->log('UPDATE', QaPrivilege::class, $p->id, null, ['granted' => true, 'until' => $until->toDateString()]);

        return $p;
    }

    public function withdraw(QaPrivilege $p, User $by, bool $revoke, string $reason): QaPrivilege
    {
        if ($p->status !== 'granted') {
            throw ValidationException::withMessages(['status' => __('Only a privilege in force can be withdrawn.')]);
        }
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => __('Say why.')]);
        }
        $p->update(['status' => $revoke ? 'revoked' : 'suspended', 'note' => mb_substr($reason, 0, 500)]);
        $this->audit->log('UPDATE', QaPrivilege::class, $p->id, null, ['status' => $p->status]);

        return $p;
    }

    /** Does this person hold the privilege today? Other screens ask this. */
    public function holds(int $organizationId, int $employeeId, string $code): bool
    {
        return QaPrivilege::query()->where(['organization_id' => $organizationId, 'employee_id' => $employeeId, 'status' => 'granted'])->where('expires_on', '>=', today()->toDateString())
            ->whereHas('item', fn ($q) => $q->where('code', strtoupper($code)))->exists();
    }

    /** Expire what has run out, suspend what lost its licence, warn about what is about to expire. @return array{expired:int,suspended:int,warned:int} */
    public function maintain(int $organizationId): array
    {
        $out = ['expired' => 0, 'suspended' => 0, 'warned' => 0];
        $today = today()->toDateString();
        foreach (QaPrivilege::query()->with('item')->where('organization_id', $organizationId)->where('status', 'granted')->get() as $p) {
            if ($p->expires_on && $p->expires_on->toDateString() < $today) {
                $p->update(['status' => 'expired']);
                $out['expired']++;

                continue;
            }
            $need = $p->item->requires_credential;
            if ($need && ! DB::table('hr_credentials')->where(['employee_id' => $p->employee_id, 'credential_type' => $need, 'status' => 'active'])->where(fn ($q) => $q->whereNull('expires_on')->orWhere('expires_on', '>=', $today))->exists()) {
                $p->update(['status' => 'suspended', 'note' => 'The required licence is no longer valid.']);
                $out['suspended']++;

                continue;
            }
            if ($p->expires_on && $p->expires_on->toDateString() <= today()->addDays($this->policy->int('privilege.expiry_warning_days', 60))->toDateString()) {
                $out['warned']++;
            }
        }
        if ($out['warned'] > 0) {
            $this->alerts->raise($organizationId, 'info', __(':n privilege(s) expire soon.', ['n' => $out['warned']]), QaPrivilege::class, 0, 'qa-priv-warn-'.$organizationId.'-'.$today, 'quality.privilege.manage', false);
        }

        return $out;
    }
}
