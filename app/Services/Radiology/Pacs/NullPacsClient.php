<?php

namespace App\Services\Radiology\Pacs;

use App\Contracts\Radiology\PacsClientInterface;

/**
 * Default no-op PacsClientInterface adapter — used when no PACS server is configured/active,
 * or as the fallback for a RadiologyPacsServer row with adapter_type='null'. Keeps every
 * caller working (gracefully reporting "not found"/unavailable) without a real PACS connection.
 */
class NullPacsClient implements PacsClientInterface
{
    public function findStudy(string $accessionNumber): ?array
    {
        return null;
    }

    public function queryStudies(array $criteria): array
    {
        return [];
    }

    public function getStudyMetadata(string $studyInstanceUid): ?array
    {
        return null;
    }

    public function getViewerUrl(string $studyInstanceUid): ?string
    {
        return null;
    }
}
