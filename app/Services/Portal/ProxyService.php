<?php

namespace App\Services\Portal;

use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalNotification;
use App\Models\Portal\PortalProxy;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** A relative asks to act for a patient; staff check the evidence and decide; the patient (or the relative) can end it at any time (S10). */
class ProxyService
{
    public function __construct(private readonly PortalPolicy $policy, private readonly AuditLogger $audit) {}

    /** Same answer whether or not the person exists. Returns true when a request was really recorded (for tests and staff; the caller must not show it). */
    public function request(PatientPortalAccount $account, string $identifier, string $dateOfBirth, string $relationship, array $scopes, ?string $evidence): bool
    {
        if (! in_array($relationship, (array) $this->policy->get('proxy.relationships', []), true)) {
            throw new PortalException(__('Choose how you are related.'));
        }
        $scopes = array_values(array_intersect($scopes, (array) $this->policy->get('proxy.scopes', [])));
        if ($scopes === []) {
            throw new PortalException(__('Choose what you need to see or do.'));
        }
        $patient = Patient::query()->where('organization_id', $account->organization_id)->where(fn ($q) => $q->where('enterprise_patient_no', trim($identifier))->orWhere('national_identifier', trim($identifier)))->first();
        $dob = rescue(fn () => CarbonImmutable::parse($dateOfBirth)->toDateString(), null, false);
        if ($patient === null || $patient->id === $account->patient_id || $patient->date_of_birth?->toDateString() !== $dob || $patient->deceased_at !== null) {
            return false;
        }
        if (PortalProxy::query()->where(['account_id' => $account->id, 'patient_id' => $patient->id])->whereIn('status', ['requested', 'active'])->exists()) {
            return false;
        }
        $proxy = PortalProxy::create(['organization_id' => $account->organization_id, 'account_id' => $account->id, 'patient_id' => $patient->id, 'relationship' => $relationship, 'scopes' => $scopes,
            'status' => 'requested', 'evidence' => mb_substr((string) $evidence, 0, 300) ?: null, 'requested_at' => now()]);
        $this->audit->log('CREATE', PortalProxy::class, $proxy->id, null, ['patient_id' => $patient->id, 'relationship' => $relationship, 'scopes' => $scopes]);

        return true;
    }

    public function approve(PortalProxy $proxy, User $by, ?array $scopes = null, ?string $validTo = null, ?string $note = null): PortalProxy
    {
        return DB::transaction(function () use ($proxy, $by, $scopes, $validTo, $note) {
            $proxy = PortalProxy::query()->lockForUpdate()->findOrFail($proxy->id);
            if ($proxy->status !== 'requested') {
                throw new PortalException(__('This request has already been decided.'), 409);
            }
            $patient = Patient::query()->findOrFail($proxy->patient_id);
            $until = $validTo ? CarbonImmutable::parse($validTo) : CarbonImmutable::now()->addMonths($this->policy->int('proxy.default_valid_months', 12));
            if (in_array($proxy->relationship, ['parent', 'guardian'], true) && $patient->date_of_birth) { // the approval ends when the child becomes an adult
                $adult = CarbonImmutable::instance($patient->date_of_birth)->addYears($this->policy->int('proxy.minor_age_years', 18));
                if ($adult->isFuture() && $adult->lt($until)) {
                    $until = $adult;
                }
            }
            $proxy->update(['status' => 'active', 'scopes' => array_values(array_intersect($scopes ?? (array) $proxy->scopes, (array) $this->policy->get('proxy.scopes', []))), 'decided_by' => $by->id, 'decided_at' => now(),
                'valid_from' => now()->toDateString(), 'valid_to' => $until->toDateString(), 'decision_note' => $note]);
            $this->audit->log('UPDATE', PortalProxy::class, $proxy->id, ['status' => 'requested'], ['status' => 'active', 'by' => $by->id]);
            $this->tell($patient, __('A relative can now see parts of your record'), __('Approved by the hospital. You can end this any time under Family and access.'));

            return $proxy;
        });
    }

    public function reject(PortalProxy $proxy, User $by, string $note): PortalProxy
    {
        if ($proxy->status !== 'requested') {
            throw new PortalException(__('This request has already been decided.'), 409);
        }
        $proxy->update(['status' => 'rejected', 'decided_by' => $by->id, 'decided_at' => now(), 'decision_note' => mb_substr($note, 0, 300)]);
        $this->audit->log('UPDATE', PortalProxy::class, $proxy->id, ['status' => 'requested'], ['status' => 'rejected', 'by' => $by->id]);

        return $proxy;
    }

    /** Ended by staff, by the patient, or by the relative themselves. */
    public function revoke(PortalProxy $proxy, string $reason, ?User $by = null): PortalProxy
    {
        if (! in_array($proxy->status, ['requested', 'active'], true)) {
            return $proxy;
        }
        $proxy->update(['status' => 'revoked', 'decided_by' => $by->id ?? $proxy->decided_by, 'decided_at' => now(), 'valid_to' => now()->toDateString(), 'decision_note' => mb_substr($reason, 0, 300)]);
        $this->audit->log('UPDATE', PortalProxy::class, $proxy->id, null, ['status' => 'revoked', 'reason' => $reason]);

        return $proxy;
    }

    public function expireDue(): int
    {
        $due = PortalProxy::query()->where('status', 'active')->whereNotNull('valid_to')->where('valid_to', '<', now()->toDateString())->get();
        foreach ($due as $proxy) {
            $proxy->update(['status' => 'expired']);
        }

        return $due->count();
    }

    private function tell(Patient $patient, string $title, string $body): void
    {
        PortalNotification::create(['organization_id' => $patient->organization_id, 'patient_id' => $patient->id, 'kind' => 'consent', 'title' => $title, 'body' => $body, 'link' => '/portal/family']);
    }
}
