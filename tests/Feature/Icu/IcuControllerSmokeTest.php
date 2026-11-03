<?php

namespace Tests\Feature\Icu;

use App\Services\Icu\IcuAssessmentService;
use App\Services\Icu\IcuProgressNoteService;
use App\Services\TenantContextResolver;

class IcuControllerSmokeTest extends IcuTestCase
{
    public function test_every_get_page_loads(): void
    {
        $episode = $this->makeEpisode();
        app(IcuAssessmentService::class)->create($episode, ['sections' => []], $this->user);
        app(IcuProgressNoteService::class)->create($episode, ['subjective' => 'x'], $this->user);

        $request = $this->makeAdmissionRequest();

        foreach ([
            '/icu',
            '/icu/bed-board',
            '/icu/admission-requests',
            '/icu/admission-requests/create',
            '/icu/admission-requests/'.$request->public_id,
            '/icu/episodes',
            '/icu/episodes/create',
            '/icu/episodes/'.$episode->public_id,
            '/icu/episodes/'.$episode->public_id.'/assessment',
            '/icu/episodes/'.$episode->public_id.'/flowsheet',
            '/icu/episodes/'.$episode->public_id.'/discharge/create',
            '/icu/procedures',
            '/icu/procedures/create',
            '/icu/alerts',
            '/icu/critical-events',
            '/icu/reports/occupancy',
            '/icu/reports/utilization',
            '/icu/reports/outcomes',
            '/icu/settings',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_api_surfaces_load(): void
    {
        $episode = $this->makeEpisode();

        // Tenant context is per-request state cleared at the end of each request — re-established
        // before each call here, same fix as OtControllerSmokeTest's equivalent API test.
        foreach ([
            '/api/v1/icu/dashboard',
            '/api/v1/icu/bed-board',
            '/api/v1/icu/episodes',
            '/api/v1/icu/episodes/'.$episode->public_id,
            '/api/v1/icu/admission-requests',
        ] as $url) {
            app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
            app(TenantContextResolver::class)->setBranchId($this->branch->id);
            $this->getJson($url)->assertOk();
        }
    }
}
