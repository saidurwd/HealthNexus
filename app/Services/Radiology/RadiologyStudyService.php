<?php

namespace App\Services\Radiology;

use App\Models\Radiology\RadiologyStudy;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Radiology\Pacs\PacsClientFactory;
use Illuminate\Validation\ValidationException;

/**
 * The only path a controller should use to reach a PACS viewer — never exposes a raw PACS URL
 * directly (spec §35/§58). Every call is audited ("PACS study accessed").
 */
class RadiologyStudyService
{
    public function __construct(
        private readonly PacsClientFactory $factory,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function viewerUrl(RadiologyStudy $study, User $user): string
    {
        if (! $study->hasImages()) {
            throw ValidationException::withMessages(['study' => 'This study has not yet been reconciled with PACS — no images are available.']);
        }

        $server = $study->pacsServer;

        if (! $server || ! $server->is_active) {
            throw ValidationException::withMessages(['study' => 'The PACS server for this study is not available.']);
        }

        $url = $this->factory->for($server)->getViewerUrl($study->study_instance_uid);

        if (! $url) {
            throw ValidationException::withMessages(['study' => 'Unable to generate a viewer URL for this study.']);
        }

        $this->auditLogger->log('PACS_STUDY_ACCESSED', RadiologyStudy::class, $study->id, null, ['user_id' => $user->id], request());

        return $url;
    }
}
