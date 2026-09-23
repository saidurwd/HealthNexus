<?php

namespace App\Services\Radiology\Pacs;

use App\Contracts\Radiology\PacsClientInterface;
use App\Models\Radiology\RadiologyPacsServer;

/**
 * Resolves the adapter per RadiologyPacsServer row (Phase 6 plan decision #2) — not a single
 * container-wide binding, since PACS configuration is genuinely per-server data (a company may
 * configure more than one PACS server).
 */
class PacsClientFactory
{
    public function for(RadiologyPacsServer $server): PacsClientInterface
    {
        return match ($server->adapter_type) {
            'orthanc' => new OrthancPacsClient($server),
            default => new NullPacsClient,
        };
    }
}
