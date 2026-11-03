<?php

namespace App\Services\Interop;

use App\Models\Integration\IntegrationCredential;
use App\Models\Integration\IntegrationPartner;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Secrets for talking to a partner and for checking what a partner sends (S01). Stored encrypted. Rotation never needs a deploy or a gap: a new version is active at once and the
 * old one keeps working until `valid_to` (the overlap), then stops. Plain values are shown once, when made.
 */
class CredentialService
{
    public function __construct(private readonly AuditLogger $audit, private readonly InteropPolicy $policy) {}

    /** @return array{0:IntegrationCredential,1:string} the row and the plain secret (show it once) */
    public function create(IntegrationPartner $partner, string $label, string $kind, ?string $secret = null, ?User $by = null, ?string $keyId = null): array
    {
        $plain = $secret ?? Str::random(48);
        $row = IntegrationCredential::create(['partner_id' => $partner->id, 'label' => $label, 'kind' => $kind, 'key_id' => $keyId ?? 'k'.Str::lower(Str::random(8)), 'secret' => $plain, 'version' => 1, 'status' => 'active', 'valid_from' => now(), 'rotated_by' => $by?->id]);
        $this->audit->log('CREATE', IntegrationCredential::class, $row->id, null, ['partner' => $partner->code, 'label' => $label, 'kind' => $kind]);

        return [$row, $plain];
    }

    /** @return array{0:IntegrationCredential,1:string} */
    public function rotate(IntegrationPartner $partner, string $label, ?User $by = null, ?int $overlapHours = null, ?string $secret = null): array
    {
        return DB::transaction(function () use ($partner, $label, $by, $overlapHours, $secret) {
            $current = IntegrationCredential::query()->where(['partner_id' => $partner->id, 'label' => $label])->where('status', 'active')->orderByDesc('version')->lockForUpdate()->firstOrFail();
            $until = CarbonImmutable::now()->addHours($overlapHours ?? $this->policy->int('credentials.overlap_hours', 24));
            IntegrationCredential::query()->where(['partner_id' => $partner->id, 'label' => $label])->where('status', 'active')->update(['status' => 'retiring', 'valid_to' => $until]);
            $plain = $secret ?? Str::random(48);
            $row = IntegrationCredential::create(['partner_id' => $partner->id, 'label' => $label, 'kind' => $current->kind, 'key_id' => 'k'.Str::lower(Str::random(8)), 'secret' => $plain, 'version' => $current->version + 1, 'status' => 'active', 'valid_from' => now(), 'rotated_by' => $by?->id]);
            $this->audit->log('UPDATE', IntegrationCredential::class, $row->id, ['version' => $current->version], ['version' => $row->version, 'overlap_until' => $until->toDateTimeString()]);

            return [$row, $plain];
        });
    }

    public function revoke(IntegrationCredential $credential, ?User $by = null): void
    {
        $credential->update(['status' => 'revoked', 'valid_to' => now()]);
        $this->audit->log('UPDATE', IntegrationCredential::class, $credential->id, null, ['status' => 'revoked', 'by' => $by?->id]);
    }

    /** The secret to use when we call out: the newest version that still works. */
    public function current(IntegrationPartner $partner, string $label): ?IntegrationCredential
    {
        return $this->usable($partner, $label)->first();
    }

    /** Does this secret match a version that still works? (inbound signatures and keys) Constant-time. */
    public function verify(IntegrationPartner $partner, string $label, string $candidate): bool
    {
        foreach ($this->usable($partner, $label) as $cred) {
            if (hash_equals((string) $cred->secret, $candidate)) {
                $cred->forceFill(['last_used_at' => now()])->save();

                return true;
            }
        }

        return false;
    }

    /** @return Collection<int, IntegrationCredential> every version that works now, newest first */
    public function usable(IntegrationPartner $partner, string $label)
    {
        return IntegrationCredential::query()->where(['partner_id' => $partner->id, 'label' => $label])->whereIn('status', ['active', 'retiring'])
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', now()))->orderByDesc('version')->get();
    }

    /** Retiring versions past their time stop working. */
    public function expireOld(): int
    {
        return IntegrationCredential::query()->where('status', 'retiring')->where('valid_to', '<=', now())->update(['status' => 'revoked']);
    }

    /** Client token for the FHIR API (guideline override 5). The plain token is returned once. */
    public function issueToken(IntegrationPartner $partner, array $abilities, ?int $days = null): string
    {
        $allowed = array_values(array_intersect($abilities, (array) config('interop.tokens.abilities')));
        $token = $partner->createToken('fhir', $allowed, CarbonImmutable::now()->addDays($days ?? $this->policy->int('tokens.ttl_days', 90)));
        $this->audit->log('CREATE', IntegrationPartner::class, $partner->id, null, ['token' => 'issued', 'abilities' => $allowed]);

        return $token->plainTextToken;
    }

    public function revokeTokens(IntegrationPartner $partner): void
    {
        $partner->tokens()->delete();
        $this->audit->log('UPDATE', IntegrationPartner::class, $partner->id, null, ['tokens' => 'revoked']);
    }
}
