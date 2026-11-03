<?php

namespace App\Services\Interop;

use App\Models\Integration\IntegrationIdMap;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * Identifiers in both directions (S04). `local` scope: the opaque id WE show outside for a record (its own public_id when it has one, otherwise a ULID kept here), so a numeric
 * database id never leaves the server. A partner scope: that partner's own id for the same record, found from either side.
 */
class IdMapper
{
    public const LOCAL = 'local';

    /** The id we expose for a record. */
    public function expose(Model $model, ?string $entityType = null): string
    {
        $publicId = $model->getAttribute('public_id');
        if (is_string($publicId) && $publicId !== '') {
            return $publicId;
        }

        $organizationId = $model instanceof Organization ? (int) $model->getKey() : (int) $model->getAttribute('organization_id');

        return $this->exposeId($entityType ?? class_basename($model), (int) $model->getKey(), $organizationId);
    }

    public function exposeId(string $entityType, int $localId, int $organizationId): string
    {
        $row = IntegrationIdMap::query()->where(['organization_id' => $organizationId, 'scope' => self::LOCAL, 'entity_type' => $entityType, 'local_id' => $localId])->first();
        if ($row) {
            return $row->external_id;
        }
        try {
            return IntegrationIdMap::create(['organization_id' => $organizationId, 'scope' => self::LOCAL, 'entity_type' => $entityType, 'local_id' => $localId, 'external_id' => (string) Str::ulid()])->external_id;
        } catch (QueryException) { // someone else made it a moment ago
            return (string) IntegrationIdMap::query()->where(['organization_id' => $organizationId, 'scope' => self::LOCAL, 'entity_type' => $entityType, 'local_id' => $localId])->value('external_id');
        }
    }

    /** Local numeric id for an id we exposed (null when it is not one of ours). */
    public function resolve(string $entityType, string $exposedId, int $organizationId): ?int
    {
        $id = IntegrationIdMap::query()->where(['organization_id' => $organizationId, 'scope' => self::LOCAL, 'entity_type' => $entityType, 'external_id' => $exposedId])->value('local_id');

        return $id === null ? null : (int) $id;
    }

    public function link(string $partnerCode, string $entityType, int $localId, string $externalId, int $organizationId): IntegrationIdMap
    {
        return IntegrationIdMap::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'scope' => $partnerCode, 'entity_type' => $entityType, 'local_id' => $localId],
            ['external_id' => $externalId],
        );
    }

    public function externalFor(string $partnerCode, string $entityType, int $localId, int $organizationId): ?string
    {
        return IntegrationIdMap::query()->where(['organization_id' => $organizationId, 'scope' => $partnerCode, 'entity_type' => $entityType, 'local_id' => $localId])->value('external_id');
    }

    public function localFor(string $partnerCode, string $entityType, string $externalId, int $organizationId): ?int
    {
        $id = IntegrationIdMap::query()->where(['organization_id' => $organizationId, 'scope' => $partnerCode, 'entity_type' => $entityType, 'external_id' => $externalId])->value('local_id');

        return $id === null ? null : (int) $id;
    }
}
