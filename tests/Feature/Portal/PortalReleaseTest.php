<?php

namespace Tests\Feature\Portal;

use App\Models\Laboratory\LabCriticalResultAlert;
use App\Models\Laboratory\LabResult;
use App\Models\Portal\PortalOutbox;
use App\Models\Portal\PortalReleasePolicy;
use App\Models\Portal\PortalResultRelease;
use App\Services\Portal\PortalException;
use App\Services\Portal\ResultReleaseService;

class PortalReleaseTest extends PortalTestCase
{
    private function service(): ResultReleaseService
    {
        return app(ResultReleaseService::class);
    }

    private function policy(string $mode, string $scope = 'default', string $key = 'all', int $delay = 0, bool $sensitive = false): void
    {
        PortalReleasePolicy::create(['organization_id' => $this->organization->id, 'scope_type' => $scope, 'scope_key' => $key, 'mode' => $mode, 'delay_hours' => $delay, 'sensitive' => $sensitive]);
    }

    public function test_by_default_a_result_waits_for_a_clinician_and_the_patient_cannot_see_it(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $this->labReport($patient);

        $this->assertSame(1, $this->service()->sync());
        $row = PortalResultRelease::query()->first();
        $this->assertSame('pending', $row->status);

        $res = $this->getJson('/api/v1/portal/results', $headers)->assertOk();
        $this->assertCount(0, $res->json('data.released'));
        $this->assertCount(1, $res->json('data.not_yet_available'));
        $this->assertStringNotContainsString('Hemoglobin', json_encode($res->json()), 'a withheld result must not leak its content');
        $this->getJson("/api/v1/portal/results/{$row->public_id}", $headers)->assertNotFound();

        $this->service()->release($row, $this->actor(['portal.release.manage']));
        $this->getJson('/api/v1/portal/results', $headers)->assertJsonCount(1, 'data.released');
        $this->getJson("/api/v1/portal/results/{$row->public_id}", $headers)->assertOk();
    }

    public function test_sync_is_safe_to_repeat(): void
    {
        $this->labReport($this->patient());
        $this->assertSame(1, $this->service()->sync());
        $this->assertSame(0, $this->service()->sync());
        $this->assertSame(1, PortalResultRelease::query()->count());
    }

    public function test_an_immediate_policy_shows_the_result_at_once(): void
    {
        $this->policy('immediate');
        $this->labReport($this->patient());
        $this->service()->sync();

        $this->assertSame('released', PortalResultRelease::query()->first()->status);
    }

    public function test_a_sensitive_test_is_never_released_straight_away_even_under_an_immediate_policy(): void
    {
        $this->policy('immediate');
        $this->labReport($this->patient(), 'HIV antibody screen');
        $this->service()->sync();

        $row = PortalResultRelease::query()->first();
        $this->assertSame('pending', $row->status);
        $this->assertSame('after_review', $row->mode);
    }

    public function test_a_category_rule_beats_the_default_and_the_strictest_wins(): void
    {
        $this->policy('immediate');
        $this->policy('never', 'lab_category', 'HISTO');
        $this->labReport($this->patient(), 'Tissue exam', false, 'HISTO');
        $this->service()->sync();

        $this->assertSame('never', PortalResultRelease::query()->first()->status);
    }

    public function test_a_delayed_result_appears_only_after_its_delay(): void
    {
        $this->policy('delayed', 'default', 'all', 24);
        $this->labReport($this->patient());
        $this->service()->sync();
        $row = PortalResultRelease::query()->first();
        $this->assertSame('pending', $row->status);

        $this->assertSame(0, $this->service()->releaseDue());
        $this->travel(25)->hours();
        $this->assertSame(1, $this->service()->releaseDue());
        $this->assertSame('released', $row->fresh()->status);
    }

    public function test_a_critical_result_is_never_released_automatically_and_needs_an_acknowledged_alert(): void
    {
        $this->policy('immediate');
        $patient = $this->patient();
        $this->labReport($patient, 'Potassium', true);
        $this->service()->sync();
        $row = PortalResultRelease::query()->first();
        $this->assertSame('held_critical', $row->status);
        $this->assertTrue($row->critical);

        $this->travel(48)->hours();
        $this->assertSame(0, $this->service()->releaseDue());
        $this->assertSame('held_critical', $row->fresh()->status);

        $clinician = $this->actor(['portal.release.manage']);
        try {
            $this->service()->release($row, $clinician);
            $this->fail('released without an acknowledged critical alert');
        } catch (PortalException $e) {
            $this->assertSame(409, $e->status);
        }

        $result = LabResult::query()->where('critical_flag', true)->first();
        LabCriticalResultAlert::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'result_id' => $result->id, 'detected_at' => now(), 'acknowledged_by' => $clinician->id, 'acknowledged_at' => now()]);
        $this->assertSame('released', $this->service()->release($row, $clinician)->status);
    }

    public function test_an_unacknowledged_critical_alert_blocks_release(): void
    {
        $patient = $this->patient();
        $this->labReport($patient, 'Potassium', true);
        $this->service()->sync();
        $result = LabResult::query()->where('critical_flag', true)->first();
        LabCriticalResultAlert::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'result_id' => $result->id, 'detected_at' => now()]);

        $this->expectException(PortalException::class);
        $this->service()->release(PortalResultRelease::query()->first(), $this->actor(['portal.release.manage']));
    }

    public function test_a_result_set_to_in_person_only_needs_a_reason_to_go_online(): void
    {
        $this->policy('never');
        $this->labReport($this->patient());
        $this->service()->sync();
        $row = PortalResultRelease::query()->first();
        $user = $this->actor(['portal.release.manage']);

        try {
            $this->service()->release($row, $user);
            $this->fail('released without a reason');
        } catch (PortalException) {
        }
        $this->assertSame('released', $this->service()->release($row, $user, 'Patient lives abroad; doctor agreed')->status);
    }

    public function test_withholding_takes_a_released_result_back_out_of_sight(): void
    {
        $this->policy('immediate');
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $this->labReport($patient);
        $this->service()->sync();
        $this->getJson('/api/v1/portal/results', $headers)->assertJsonCount(1, 'data.released');

        $this->service()->withhold(PortalResultRelease::query()->first(), $this->actor(['portal.release.manage']), 'Clinician wants to explain first');
        app('auth')->forgetGuards();
        $this->getJson('/api/v1/portal/results', $headers)->assertJsonCount(0, 'data.released');
    }

    public function test_the_ready_text_message_never_says_what_the_result_is(): void
    {
        $this->policy('immediate');
        $this->labReport($this->patient(['phone' => '01712345678']), 'HIV antibody screen');
        $this->policy('immediate', 'lab_category', 'X');
        $this->service()->sync();
        $row = PortalResultRelease::query()->first();
        $this->service()->release($row, $this->actor(['portal.release.manage']));

        $sms = PortalOutbox::query()->where('template', 'result_ready')->first();
        $this->assertNotNull($sms);
        $this->assertStringNotContainsString('HIV', $sms->body);
        $this->assertStringNotContainsString('antibody', $sms->body);
    }

    public function test_a_patient_cannot_open_another_patients_released_result(): void
    {
        $this->policy('immediate');
        $other = $this->patient();
        $this->labReport($other);
        $this->service()->sync();
        $row = PortalResultRelease::query()->first();

        $this->getJson("/api/v1/portal/results/{$row->public_id}", $this->bearer($this->account($this->patient())))->assertNotFound();
    }
}
