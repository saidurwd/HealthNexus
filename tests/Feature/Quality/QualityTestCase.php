<?php

namespace Tests\Feature\Quality;

use App\Models\Quality\QaIncident;
use App\Models\User;
use App\Services\Quality\IncidentService;
use App\Services\Quality\QualityInstaller;
use Tests\Feature\Portal\PortalTestCase;

/** A hospital, a branch, patients and staff with exactly the quality rights a test names. */
abstract class QualityTestCase extends PortalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app(QualityInstaller::class)->install($this->organization->id);
    }

    protected function manager(): User
    {
        return $this->actor(['quality.dashboard.view', 'quality.incident.report', 'quality.incident.view', 'quality.incident.triage', 'quality.incident.amend', 'quality.investigation.manage', 'quality.investigation.review', 'quality.action.view', 'quality.action.manage', 'quality.action.verify']);
    }

    /** @param array<string,mixed> $o */
    protected function incident(?User $by = null, array $o = []): QaIncident
    {
        return app(IncidentService::class)->report($this->organization->id, array_merge(['kind' => 'incident', 'category' => 'fall', 'harm' => 'minor', 'occurred_at' => '2026-09-09 14:30', 'description' => 'A patient slipped near the washroom door.', 'branch_id' => $this->branch->id], $o), $by);
    }
}
