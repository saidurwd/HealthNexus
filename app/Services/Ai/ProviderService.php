<?php

namespace App\Services\Ai;

use App\Models\Ai\AiModel;
use App\Models\Ai\AiProvider;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Where AI runs and with what. A provider names its place (on premise, Bangladesh, abroad), the highest class of data it may receive, and — for sending identifiable data abroad — the recorded legal approval.
 * No secret is stored: only the name of the environment variable that holds it. This list is also the register of every outside service that receives personal data.
 */
class ProviderService
{
    public function __construct(private readonly AiPolicy $policy, private readonly AuditLogger $audit) {}

    /** @param array<string,mixed> $d name, kind, location, endpoint, default_model, secret_env, max_level, purpose */
    public function add(int $organizationId, array $d, User $by): AiProvider
    {
        if (! in_array($d['kind'] ?? '', $this->policy->list('provider.kinds'), true) || ! in_array($d['location'] ?? '', $this->policy->list('provider.locations'), true) || mb_strlen(trim((string) ($d['name'] ?? ''))) < 3) {
            throw ValidationException::withMessages(['name' => __('Give the provider a name, a kind and a place.')]);
        }
        if (($d['kind'] ?? '') === 'http' && ! filter_var($d['endpoint'] ?? '', FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages(['endpoint' => __('Give the address of the service.')]);
        }
        if (! empty($d['secret_env']) && ! preg_match('/^[A-Z][A-Z0-9_]{2,60}$/', (string) $d['secret_env'])) {
            throw ValidationException::withMessages(['secret_env' => __('Give the NAME of the environment variable that holds the key (capital letters), not the key itself.')]);
        }
        $level = $d['max_level'] ?? 'C3';
        if (! in_array($level, $this->policy->list('data_levels'), true)) {
            throw ValidationException::withMessages(['max_level' => __('Choose a data class.')]);
        }
        $external = in_array($d['location'], $this->policy->list('provider.external_locations'), true);
        $p = AiProvider::create(['organization_id' => $organizationId, 'name' => mb_substr(trim($d['name']), 0, 100), 'kind' => $d['kind'], 'location' => $d['location'], 'endpoint' => $d['endpoint'] ?? null, 'default_model' => $d['default_model'] ?? null,
            'secret_env' => $d['secret_env'] ?? null, 'max_level' => $external && $level === 'C4' ? 'C3' : $level, 'allows_identifiable' => false, 'purpose' => isset($d['purpose']) ? mb_substr((string) $d['purpose'], 0, 300) : null, 'status' => 'disabled']);
        $this->audit->log('CREATE', AiProvider::class, $p->id, null, ['name' => $p->name, 'location' => $p->location]);

        return $p;
    }

    /** Record the legal approval to send identifiable data to this provider. Needed for any provider abroad. */
    public function approveIdentifiable(AiProvider $p, User $by, string $reference): AiProvider
    {
        if (mb_strlen(trim($reference)) < 5) {
            throw ValidationException::withMessages(['legal_ref' => __('Give the reference of the written legal approval.')]);
        }
        $p->update(['allows_identifiable' => true, 'max_level' => 'C4', 'legal_ref' => mb_substr(trim($reference), 0, 120), 'legal_approved_by' => $by->id, 'legal_approved_at' => now()]);
        $this->audit->log('UPDATE', AiProvider::class, $p->id, null, ['identifiable_allowed' => true, 'legal_ref' => $p->legal_ref]);

        return $p;
    }

    public function revokeIdentifiable(AiProvider $p, User $by): AiProvider
    {
        $p->update(['allows_identifiable' => false, 'max_level' => 'C3']);
        $this->audit->log('UPDATE', AiProvider::class, $p->id, null, ['identifiable_allowed' => false]);

        return $p;
    }

    public function setEnabled(AiProvider $p, bool $on, User $by): AiProvider
    {
        if ($on && $p->kind === 'http' && empty($p->secret_env)) {
            throw ValidationException::withMessages(['status' => __('Name the environment variable that holds the key before enabling this provider.')]);
        }
        $p->update(['status' => $on ? 'enabled' : 'disabled']);
        $this->audit->log('UPDATE', AiProvider::class, $p->id, null, ['status' => $p->status]);

        return $p;
    }

    public function addModel(AiProvider $p, string $name, string $version, ?string $notes = null): AiModel
    {
        if (mb_strlen(trim($name)) < 2 || mb_strlen(trim($version)) < 1) {
            throw ValidationException::withMessages(['name' => __('Give the model a name and a version.')]);
        }
        if (AiModel::query()->where(['provider_id' => $p->id, 'name' => trim($name), 'version' => trim($version)])->exists()) {
            throw ValidationException::withMessages(['version' => __('That model version is already registered.')]);
        }

        return AiModel::create(['organization_id' => $p->organization_id, 'provider_id' => $p->id, 'name' => mb_substr(trim($name), 0, 100), 'version' => mb_substr(trim($version), 0, 40), 'status' => 'candidate', 'notes' => $notes ? mb_substr($notes, 0, 300) : null]);
    }
}
