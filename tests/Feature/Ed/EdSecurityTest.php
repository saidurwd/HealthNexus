<?php

namespace Tests\Feature\Ed;

use App\Models\Branch;
use App\Models\BreakGlassAccess;
use App\Models\Ed\EdVisit;
use App\Models\Organization;
use App\Services\Ed\EdBayService;
use App\Services\Ed\EdBoardQuery;
use App\Services\Ed\EdConsultService;
use App\Services\Ed\EdDispositionService;
use App\Services\Ed\EdObservationService;
use App\Services\Ed\EdProtocolService;
use App\Services\Ed\EdVisitService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class EdSecurityTest extends EdTestCase
{
    private function visit(): EdVisit
    {
        return app(EdVisitService::class)->arrive($this->user, $this->branch, ['patient_id' => $this->patient()->id, 'arrival_mode' => 'walk_in']);
    }

    /** Every ED route that names a record, as [method, uri]. */
    private function recordRoutes(): array
    {
        $visit = $this->visit();
        $bay = $this->bay();
        $consult = app(EdConsultService::class)->request($visit, $this->user, 'ENT', 'routine');
        $stay = app(EdObservationService::class)->start($visit, $this->user);
        $run = app(EdProtocolService::class)->start($visit, $this->user, 'stroke');
        $step = $run->steps->first();
        $v = "/admin/emergency/visits/{$visit->public_id}";

        return [
            ['GET', $v], ['POST', "$v/triage"], ['POST', "$v/bay"], ['DELETE', "$v/bay"], ['POST', "$v/start"], ['PUT', "$v/team"],
            ['POST', "$v/resus"], ['POST', "$v/monitor"], ['POST', "$v/orders"], ['POST', "$v/prescriptions"], ['POST', "$v/medications"], ['POST', "$v/procedures"],
            ['POST', "$v/protocols"], ['POST', "$v/injuries"], ['POST', "$v/consults"], ['POST', "$v/observation"], ['POST', "$v/disposition"],
            ['POST', "/admin/emergency/consults/{$consult->public_id}/respond"], ['DELETE', "/admin/emergency/consults/{$consult->public_id}"],
            ['POST', "/admin/emergency/observation/{$stay->public_id}/end"], ['DELETE', "/admin/emergency/protocol-runs/{$run->public_id}"],
            ['POST', "/admin/emergency/protocol-steps/{$step->public_id}/done"],
            ['PUT', "/admin/emergency/bays/{$bay->public_id}/status"], ['POST', "/admin/emergency/zones/{$bay->zone->public_id}/bays"],
        ];
    }

    public function test_another_organization_can_reach_nothing_of_this_one(): void
    {
        $routes = $this->recordRoutes();
        $otherOrg = Organization::factory()->create();
        $otherBranch = Branch::factory()->create(['organization_id' => $otherOrg->id]);
        $outsider = $this->staff(['super_admin'], $otherOrg, $otherBranch);
        $this->actingAs($outsider);
        $this->context($otherOrg, $otherBranch);

        $leaks = [];
        foreach ($routes as [$method, $uri]) {
            $status = $this->call($method, $uri, [], [], [], ['HTTP_ACCEPT' => 'application/json'])->getStatusCode();
            if (! in_array($status, [403, 404], true)) {
                $leaks[] = "$method $uri → $status";
            }
        }

        $this->assertSame([], $leaks);
    }

    public function test_a_member_with_no_permissions_is_refused_everywhere(): void
    {
        $routes = $this->recordRoutes();
        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($this->staff([]));

        $leaks = [];
        foreach ($routes as [$method, $uri]) {
            $status = $this->call($method, $uri, [], [], [], ['HTTP_ACCEPT' => 'application/json'])->getStatusCode();
            if ($status !== 403) {
                $leaks[] = "$method $uri → $status";
            }
        }

        $this->assertSame([], $leaks);
    }

    public function test_another_branch_of_the_same_organization_is_refused_without_break_glass(): void
    {
        $visit = $this->visit();
        $second = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->user->branches()->attach($second->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);
        $this->context($this->organization, $second);

        $this->get("/admin/emergency/visits/{$visit->public_id}")->assertNotFound();

        BreakGlassAccess::create([
            'user_id' => $this->user->id, 'encounter_id' => $visit->encounter_id, 'patient_id' => $visit->patient_id, 'reason' => 'Covering the emergency department',
            'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'expires_at' => now()->addHour(),
        ]);
        $this->get("/admin/emergency/visits/{$visit->public_id}")->assertOk();
        $this->post("/admin/emergency/visits/{$visit->public_id}/resus", ['event_type' => 'note'])->assertNotFound();
    }

    public function test_the_numeric_id_is_not_exposed_by_the_board_feed(): void
    {
        $this->visit();
        $json = $this->getJson('/admin/emergency/board/data')->assertOk()->json();

        $this->assertMatchesRegularExpression('/^[0-9A-Z]{26}$/', $json['board']['visits'][0]['id']);
    }

    public function test_two_staff_cannot_hold_the_same_bay(): void
    {
        $bay = $this->bay();
        $a = $this->visit();
        $b = app(EdVisitService::class)->arrive($this->user, $this->branch, ['arrival_mode' => 'ambulance']);
        $service = app(EdBayService::class);
        $service->assign($a, $bay, $this->user);

        $this->expectException(ValidationException::class);
        $service->assign($b, $bay, $this->user);
    }

    public function test_every_active_patient_is_on_the_board_and_a_closed_one_is_not(): void
    {
        $visits = collect([$this->visit(), $this->visit(), $this->visit()]);
        app(EdDispositionService::class)->decide($visits[0], $this->user, ['type' => 'discharge']);

        $ids = collect(app(EdBoardQuery::class)->forBranch($this->organization->id, $this->branch->id)['visits'])->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$visits[1]->public_id, $visits[2]->public_id], $ids);
        $this->assertSame(3, EdVisit::count());
    }
}
