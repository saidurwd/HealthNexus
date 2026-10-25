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
            '/admin/icu',
            '/admin/icu/bed-board',
            '/admin/icu/admission-requests',
            '/admin/icu/admission-requests/create',
            '/admin/icu/admission-requests/'.$request->id,
            '/admin/icu/episodes',
            '/admin/icu/episodes/create',
            '/admin/icu/episodes/'.$episode->id,
            '/admin/icu/episodes/'.$episode->id.'/assessment',
            '/admin/icu/episodes/'.$episode->id.'/flowsheet',
            '/admin/icu/episodes/'.$episode->id.'/discharge/create',
            '/admin/icu/procedures',
            '/admin/icu/procedures/create',
            '/admin/icu/alerts',
            '/admin/icu/critical-events',
            '/admin/icu/reports/occupancy',
            '/admin/icu/reports/utilization',
            '/admin/icu/reports/outcomes',
            '/admin/icu/settings',
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
            '/api/v1/icu/episodes/'.$episode->id,
            '/api/v1/icu/admission-requests',
        ] as $url) {
            app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
            app(TenantContextResolver::class)->setBranchId($this->branch->id);
            $this->getJson($url)->assertOk();
        }
    }
}
