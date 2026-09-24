<?php

namespace Tests\Feature\Nursing;

class NursingControllerSmokeTest extends NursingTestCase
{
    public function test_every_get_page_loads(): void
    {
        $episode = $this->makeEpisode();
        $mar = $this->makeMar($episode);
        $carePlan = app(\App\Services\Nursing\NursingCarePlanService::class)->create($episode, $this->user);
        $handover = app(\App\Services\Nursing\NursingHandoverService::class)->prepare($episode, $this->user);
        app(\App\Services\Nursing\NursingEscalationService::class)->create($episode, ['concern' => 'x', 'recipient_type' => 'charge_nurse'], $this->user);
        app(\App\Services\Nursing\NursingNoteService::class)->create($episode, ['note_type' => 'narrative', 'content' => 'hello'], $this->user);
        app(\App\Services\Nursing\NursingTaskService::class)->create($episode, ['task_type' => 'Turn', 'due_at' => now()->addHour()]);

        foreach ([
            '/admin/nursing', '/admin/nursing/episodes', '/admin/nursing/episodes/'.$episode->id,
            '/admin/nursing/shifts', '/admin/nursing/tasks', '/admin/nursing/mar', '/admin/nursing/mar/'.$mar->id,
            '/admin/nursing/episodes/'.$episode->id.'/observations', '/admin/nursing/episodes/'.$episode->id.'/intake-output',
            '/admin/nursing/episodes/'.$episode->id.'/clinical', '/admin/nursing/episodes/'.$episode->id.'/notes',
            '/admin/nursing/care-plans/'.$carePlan->id, '/admin/nursing/handover', '/admin/nursing/handover/'.$handover->id,
            '/admin/nursing/escalations', '/admin/nursing/episodes/'.$episode->id.'/discharge-checklist',
            '/admin/nursing/reports/workload', '/admin/nursing/reports/medication', '/admin/nursing/reports/quality',
            '/admin/nursing/settings',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_administer_endpoint_works_and_second_call_is_rejected(): void
    {
        $mar = $this->makeMar($this->makeEpisode());
        $payload = ['safety_checks' => ['patient' => 1, 'medication' => 1]];

        $this->post('/admin/nursing/mar/'.$mar->id.'/administer', $payload)->assertRedirect();
        $this->assertSame('administered', $mar->fresh()->status);

        $this->post('/admin/nursing/mar/'.$mar->id.'/administer', $payload)->assertSessionHasErrors();
        $this->assertDatabaseCount('nursing_medication_administrations', 1);
    }

    public function test_api_dashboard(): void
    {
        $this->getJson('/api/v1/nursing/dashboard')->assertOk();
    }

    public function test_api_administer_then_duplicate_is_rejected(): void
    {
        $mar = $this->makeMar($this->makeEpisode());
        $payload = ['safety_checks' => ['patient' => 1]];

        $this->postJson('/api/v1/nursing/mar/'.$mar->id.'/administer', $payload)->assertOk();

        // The tenant context is per-request state; re-establish it as a real client would via its session/header.
        app(\App\Services\TenantContextResolver::class)->setCompanyId($this->company->id);
        app(\App\Services\TenantContextResolver::class)->setBranchId($this->branch->id);

        $this->postJson('/api/v1/nursing/mar/'.$mar->id.'/administer', $payload)->assertStatus(422);
    }
}
