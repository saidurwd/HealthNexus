<?php

namespace Tests\Feature\Ot;

use App\Models\Encounter;
use App\Models\Patient;
use App\Services\Ot\OtConsentService;
use App\Services\Ot\OtOperativeNoteService;
use App\Services\Ot\OtPreoperativeAssessmentService;
use App\Services\Ot\OtRecoveryService;
use App\Services\Ot\OtSurgeryRequestService;
use App\Services\TenantContextResolver;

class OtControllerSmokeTest extends OtTestCase
{
    public function test_every_get_page_loads(): void
    {
        $surgery = $this->makeDaySurgery();
        app(OtConsentService::class)->grant($surgery, $this->user);
        app(OtPreoperativeAssessmentService::class)->create($surgery, ['sections' => []], $this->user);
        app(OtOperativeNoteService::class)->create($surgery, ['findings' => 'x'], $this->user);
        $completedSurgery = $this->progressToCompleted($this->makeDaySurgery());
        app(OtRecoveryService::class)->admitToRecovery($completedSurgery);

        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $encounter = Encounter::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id]);
        $surgeryRequest = app(OtSurgeryRequestService::class)->create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id,
        ], $this->user);

        foreach ([
            '/admin/ot', '/admin/ot/surgery-requests', '/admin/ot/surgery-requests/create',
            '/admin/ot/surgery-requests/'.$surgeryRequest->public_id,
            '/admin/ot/surgeries', '/admin/ot/surgeries/create', '/admin/ot/surgeries/'.$surgery->public_id,
            '/admin/ot/theatres', '/admin/ot/theatres/create',
            '/admin/ot/procedures', '/admin/ot/procedures/create',
            '/admin/ot/surgeries/'.$surgery->public_id.'/preoperative',
            '/admin/ot/surgeries/'.$completedSurgery->public_id.'/recovery',
            '/admin/ot/reports/surgeries', '/admin/ot/reports/utilization', '/admin/ot/reports/complications',
            '/admin/ot/settings',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_api_surfaces_load(): void
    {
        $surgery = $this->makeDaySurgery();

        // Tenant context is per-request state cleared at the end of each request — re-established
        // before each call here as a real client would via its session/header, same fix as
        // NursingControllerSmokeTest's equivalent API test.
        foreach ([
            '/api/v1/ot/dashboard',
            '/api/v1/ot/surgeries',
            '/api/v1/ot/surgeries/'.$surgery->public_id,
            '/api/v1/ot/surgery-requests',
        ] as $url) {
            app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
            app(TenantContextResolver::class)->setBranchId($this->branch->id);
            $this->getJson($url)->assertOk();
        }
    }
}
