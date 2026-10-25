<?php

namespace Tests\Feature\Ed;

use App\Models\Branch;
use App\Models\Ed\EdVisit;
use App\Models\Ed\EdZone;
use App\Models\Organization;
use App\Services\Ed\EdTriageService;
use App\Services\Ed\EdVisitService;
use App\Services\ModuleManager;

/** P12 S01/S04: zones and bays, arrival form, tracking board data, scope. */
class EdScreensTest extends EdTestCase
{
    private function arrive(array $data = []): EdVisit
    {
        return app(EdVisitService::class)->arrive($this->user, $this->branch, $data + ['arrival_mode' => 'walk_in', 'chief_complaint' => 'Fever']);
    }

    public function test_zones_and_bays_are_configured_by_authorised_staff_only(): void
    {
        $this->post('/admin/emergency/zones', ['code' => 'RESUS', 'name' => 'Resuscitation', 'zone_type' => 'resuscitation'])->assertRedirect();
        $zone = EdZone::firstOrFail();
        $this->post("/admin/emergency/zones/{$zone->public_id}/bays", ['code' => 'R1'])->assertRedirect();
        $this->get('/admin/emergency/zones')->assertOk()->assertSee('Resuscitation')->assertSee('R1');

        $this->actingAs($this->staff());
        $this->get('/admin/emergency/zones')->assertForbidden();
        $this->post('/admin/emergency/zones', ['code' => 'X', 'name' => 'X', 'zone_type' => 'acute'])->assertForbidden();
    }

    public function test_arrival_form_registers_an_unknown_patient_in_one_step(): void
    {
        $this->get('/admin/emergency/arrivals/create')->assertOk()->assertSee('data-enter-next', false);

        $response = $this->post('/admin/emergency/arrivals', ['arrival_mode' => 'police', 'chief_complaint' => 'Unconscious', 'sex' => 'M', 'estimated_age' => 30]);

        $visit = EdVisit::firstOrFail();
        $response->assertRedirect(route('admin.ed.visits.show', $visit));
        $this->assertTrue($visit->is_unknown_patient);
        $this->get(route('admin.ed.visits.show', $visit))->assertOk()->assertSee($visit->visit_number)->assertSee('patient-banner', false);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ED_ARRIVAL']);
    }

    public function test_triage_assign_bay_and_start_treatment_through_the_screens(): void
    {
        $visit = $this->arrive();
        $bay = $this->bay('A1');

        $this->post(route('admin.ed.visits.triage', $visit), ['level' => 2, 'vitals' => ['pulse' => 100, 'systolic' => 110, 'diastolic' => 70]])->assertRedirect();
        $this->post(route('admin.ed.visits.bay', $visit), ['bay' => $bay->public_id])->assertRedirect();
        $this->post(route('admin.ed.visits.start', $visit))->assertRedirect();

        $visit->refresh();
        $this->assertSame('in_treatment', $visit->status);
        $this->assertSame($bay->id, $visit->bay_id);
        $this->post(route('admin.ed.visits.triage', $visit), ['level' => 99])->assertSessionHasErrors('level');
        $this->post(route('admin.ed.visits.bay', $visit), ['bay' => $bay->public_id])->assertSessionHasErrors('bay');
    }

    public function test_board_lists_every_active_patient_once_most_urgent_first_and_polls_json(): void
    {
        $slow = $this->arrive(['chief_complaint' => 'Sprain']);
        $urgent = $this->arrive(['chief_complaint' => 'Collapse']);
        $untriaged = $this->arrive(['chief_complaint' => 'New']);
        app(EdTriageService::class)->triage($slow, $this->user, ['level' => 5]);
        app(EdTriageService::class)->triage($urgent, $this->user, ['level' => 1]);

        $json = $this->getJson('/admin/emergency/board/data')->assertOk()->assertHeader('Cache-Control')->json('board');

        $this->assertSame(3, $json['counts']['total']);
        $this->assertSame([$urgent->visit_number, $slow->visit_number, $untriaged->visit_number], array_column($json['visits'], 'visit_number'));
        $this->assertCount(3, array_unique(array_column($json['visits'], 'id')), 'nobody is listed twice');
        $this->get('/admin/emergency')->assertOk()->assertSee('Emergency tracking board');
    }

    public function test_a_closed_visit_leaves_the_board_and_other_branches_and_organizations_are_invisible(): void
    {
        $mine = $this->arrive();
        $closed = $this->arrive();
        $closed->update(['status' => 'dispositioned']);

        $otherBranch = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $foreignOrg = Organization::factory()->create();
        $foreignBranch = Branch::factory()->create(['organization_id' => $foreignOrg->id]);
        $foreignStaff = $this->staff(['super_admin'], $foreignOrg, $foreignBranch);
        $elsewhere = app(EdVisitService::class)->arrive($foreignStaff, $foreignBranch, ['arrival_mode' => 'walk_in']);

        $visits = collect($this->getJson('/admin/emergency/board/data')->json('board.visits'));
        $this->assertSame([$mine->visit_number], $visits->pluck('visit_number')->all());

        $this->get(route('admin.ed.visits.show', $elsewhere))->assertNotFound();
        $this->post(route('admin.ed.visits.start', $elsewhere))->assertNotFound();

        $this->context($this->organization, $otherBranch);
        $this->get(route('admin.ed.visits.show', $mine))->assertNotFound();
    }

    public function test_board_and_visit_need_permission_and_the_module_flag(): void
    {
        $visit = $this->arrive();
        $nobody = $this->staff();
        $this->actingAs($nobody);

        $this->get('/admin/emergency')->assertForbidden();
        $this->getJson('/admin/emergency/board/data')->assertForbidden();
        $this->get(route('admin.ed.visits.show', $visit))->assertForbidden();

        $this->actingAs($this->user);
        app(ModuleManager::class)->set('ed', false, $this->organization->id);
        $this->get('/admin/emergency')->assertNotFound();
    }

    public function test_menu_entry_follows_the_module_flag_and_permission(): void
    {
        $this->get('/admin/emergency')->assertOk()->assertSee('Tracking board');
    }
}
