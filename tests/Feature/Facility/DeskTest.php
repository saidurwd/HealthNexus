<?php

namespace Tests\Feature\Facility;

use App\Models\Ed\EdVisit;
use App\Models\Facility\FacDietType;
use App\Models\Facility\FacInstrumentSet;
use App\Models\Facility\FacMealList;
use App\Models\Facility\FacMealTray;
use App\Models\Facility\FacMortuaryCase;
use App\Models\Facility\FacMortuaryUnit;
use App\Models\Facility\FacVisitorPass;
use App\Models\Facility\FacWorkOrder;
use App\Models\Organization;
use App\Models\User;
use App\Services\Facility\AmbulanceService;
use App\Services\Facility\AssetService;
use App\Services\Facility\CssdService;
use App\Services\Facility\MaintenanceService;
use App\Services\Facility\VisitorService;
use App\Services\TenantContextResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeskTest extends FacilityTestCase
{
    private function as(User $user): static
    {
        $this->actingAs($user);
        session()->put(['tenant_organization_id' => $this->organization->id, 'tenant_branch_id' => $this->branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);

        return $this;
    }

    public function test_every_screen_opens_for_the_super_user_and_is_refused_without_rights(): void
    {
        $asset = app(AssetService::class)->register($this->organization->id, ['name' => 'Ventilator', 'category' => 'biomedical', 'risk_class' => 'high'], $this->user);
        $wo = app(MaintenanceService::class)->reportBreakdown($asset, $this->user, 'It stops by itself every hour.');
        $batch = app(CssdService::class)->load($this->organization->id, 'steam', [FacInstrumentSet::query()->first()->id], $this->user);
        $this->admit();
        app(AmbulanceService::class)->add($this->organization->id, 'DM-1', 'basic');
        $pages = ['', '/assets', '/assets/'.$asset->public_id, '/assets/'.$asset->public_id.'/label', '/scan/'.$asset->qr_token, '/work-orders', '/work-orders/'.$wo->public_id, '/housekeeping', '/cleaning-audits', '/sterile-services', '/sterile-services/batches/'.$batch->public_id,
            '/sterile-services/trace', '/linen', '/waste', '/diets', '/meals', '/ambulances', '/mortuary', '/visitors', '/gate'];
        $this->as($this->user);
        foreach ($pages as $p) {
            $response = $this->get('/admin/facility'.$p);
            $this->assertTrue(in_array($response->status(), [200, 302], true), "$p gave ".$response->status());
        }
        $nobody = $this->actor([]);
        $this->as($nobody);
        foreach (['', '/assets', '/work-orders', '/housekeeping', '/sterile-services', '/linen', '/waste', '/diets', '/ambulances', '/mortuary', '/visitors', '/gate'] as $p) {
            $this->get('/admin/facility'.$p)->assertForbidden();
        }
    }

    public function test_any_staff_member_can_report_a_fault_from_the_label_but_not_manage_equipment(): void
    {
        $asset = app(AssetService::class)->register($this->organization->id, ['name' => 'Suction machine', 'category' => 'biomedical', 'risk_class' => 'medium'], $this->user);
        $nurse = $this->actor(['facility.workorder.report']);
        $this->as($nurse)->get('/admin/facility/scan/'.$asset->qr_token)->assertRedirect('/admin/facility/assets/'.$asset->public_id);
        $this->as($nurse)->get('/admin/facility/assets/'.$asset->public_id)->assertOk()->assertSee('Report a fault');
        $this->as($nurse)->post('/admin/facility/assets/'.$asset->public_id.'/breakdown', ['description' => 'It sucks nothing and makes a loud noise.', 'stop_use' => '1'])->assertSessionHas('success');
        $this->assertSame('under_maintenance', $asset->fresh()->status);
        $this->as($nurse)->post('/admin/facility/assets/'.$asset->public_id.'/retire', ['reason' => 'No longer needed here'])->assertForbidden();
        $this->as($nurse)->post('/admin/facility/assets', ['name' => 'x', 'category' => 'it'])->assertForbidden();
        $this->as($nurse)->get('/admin/facility/work-orders')->assertForbidden();
        $this->assertSame(1, FacWorkOrder::query()->where('asset_id', $asset->id)->count());
    }

    public function test_the_label_carries_a_qr_code_and_another_hospitals_equipment_is_not_found(): void
    {
        $asset = app(AssetService::class)->register($this->organization->id, ['name' => 'ECG', 'category' => 'biomedical'], $this->user);
        $this->as($this->actor(['facility.asset.label']))->get('/admin/facility/assets/'.$asset->public_id.'/label')->assertOk()->assertSee('<svg', false)->assertSee($asset->tag);
        $other = Organization::factory()->create();
        $stranger = $this->actor(['facility.asset.view']);
        $stranger->organizations()->sync([$other->id => ['access_level' => 'admin']]);
        session()->put(['tenant_organization_id' => $other->id]);
        $this->actingAs($stranger);
        app(TenantContextResolver::class)->setOrganizationId($other->id);
        $this->get('/admin/facility/assets/'.$asset->public_id)->assertNotFound();
        $this->get('/admin/facility/scan/'.$asset->qr_token)->assertNotFound();
    }

    public function test_the_gate_flow_lets_a_visitor_in_and_out_and_refuses_with_a_reason(): void
    {
        Carbon::setTestNow('2026-09-10 05:00:00');
        $adm = $this->admit();
        $issuer = $this->actor(['facility.visitor.issue', 'facility.visitor.view']);
        $guard = $this->actor(['facility.visitor.gate']);
        $this->as($issuer)->post('/admin/facility/visitors', ['admission_id' => $adm->id, 'kind' => 'visitor', 'visitor_name' => 'Karim Uddin', 'phone' => '01711000111'])->assertSessionHas('success');
        $pass = FacVisitorPass::query()->first();
        $this->as($issuer)->post('/admin/facility/gate/enter', ['number' => $pass->number])->assertForbidden();
        $this->as($guard)->get('/admin/facility/gate?number='.$pass->number)->assertOk()->assertSee('May enter');
        $this->as($guard)->post('/admin/facility/gate/enter', ['number' => $pass->number, 'gate' => 'Main'])->assertRedirect('/admin/facility/gate');
        $this->assertTrue(app(VisitorService::class)->inside($pass));
        $this->as($guard)->post('/admin/facility/gate/leave', ['number' => $pass->number])->assertRedirect('/admin/facility/gate');
        Carbon::setTestNow('2026-09-10 07:30:00');
        $this->as($guard)->get('/admin/facility/gate?number='.$pass->number)->assertOk()->assertSee('outside visiting hours');
        $this->as($guard)->post('/admin/facility/gate/enter', ['number' => $pass->number])->assertSessionHasErrors('pass');
        $this->as($guard)->post('/admin/facility/gate/enter', ['number' => 'VP-NONE'])->assertSessionHasErrors('number');
        $this->assertSame(1, DB::table('fac_gate_logs')->where('outcome', 'refused')->count());
    }

    public function test_a_body_is_released_only_with_two_people_through_the_screens(): void
    {
        $visit = EdVisit::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'status' => 'dispositioned', 'disposition_type' => 'death', 'dispositioned_at' => '2026-09-09 12:00:00']);
        $visit->patient()->update(['status' => 'deceased', 'deceased_at' => '2026-09-09 11:50:00']);
        $a = $this->actor(['facility.mortuary.view', 'facility.mortuary.manage']);
        $b = $this->actor(['facility.mortuary.view', 'facility.mortuary.release']);
        $this->as($a)->post('/admin/facility/mortuary/find')->assertSessionHas('success');
        $case = FacMortuaryCase::query()->first();
        $unit = FacMortuaryUnit::query()->first();
        $this->as($a)->post('/admin/facility/mortuary/'.$case->public_id.'/receive', ['tag_no' => 'T-9', 'unit_id' => $unit->id, 'verifier_id' => $a->id])->assertSessionHasErrors('verified_by');
        $this->as($a)->post('/admin/facility/mortuary/'.$case->public_id.'/receive', ['tag_no' => 'T-9', 'unit_id' => $unit->id, 'verifier_id' => $b->id])->assertSessionHas('success');
        $this->as($a)->post('/admin/facility/mortuary/'.$case->public_id.'/release', ['released_to' => 'Abdul Karim', 'relation' => 'Son', 'id_type' => 'national_id', 'id_number' => '19900011223344'])->assertSessionHas('success');
        $this->as($a)->post('/admin/facility/mortuary/'.$case->public_id.'/authorise')->assertForbidden();
        $this->as($b)->get('/admin/facility/mortuary')->assertOk()->assertSee('Abdul Karim')->assertDontSee('19900011223344');
        $this->as($b)->post('/admin/facility/mortuary/'.$case->public_id.'/authorise')->assertSessionHas('success');
        $this->assertSame('released', $case->fresh()->status);
    }

    public function test_diet_orders_and_meal_lists_work_from_the_screens(): void
    {
        $adm = $this->admit();
        $dietitian = $this->actor(['facility.diet.view', 'facility.diet.order', 'facility.diet.kitchen']);
        $type = FacDietType::query()->where('code', 'DIABETIC')->first();
        $this->as($dietitian)->post('/admin/facility/diets/orders', ['admission_id' => $adm->id, 'diet_type_id' => $type->id, 'texture' => 'soft', 'allergies' => 'Peanuts'])->assertSessionHas('success');
        $this->as($dietitian)->get('/admin/facility/diets')->assertOk()->assertSee('Peanuts');
        $this->as($dietitian)->get('/admin/facility/meals?meal=lunch&date=2026-09-10')->assertOk()->assertSee('Peanuts');
        $tray = FacMealTray::query()->first();
        $this->as($dietitian)->post('/admin/facility/meals/trays/'.$tray->id, ['status' => 'served'])->assertSessionHas('success');
        $list = FacMealList::query()->first();
        $this->as($dietitian)->post('/admin/facility/meals/'.$list->public_id.'/close')->assertSessionHas('success');
        $nurse = $this->actor(['facility.diet.view', 'facility.diet.order']);
        $this->as($nurse)->post('/admin/facility/meals/trays/'.$tray->id, ['status' => 'refused'])->assertForbidden();
    }

    public function test_the_read_only_api_needs_a_login_the_right_and_the_header(): void
    {
        $asset = app(AssetService::class)->register($this->organization->id, ['name' => 'Ventilator', 'category' => 'biomedical', 'risk_class' => 'high'], $this->user);
        app(MaintenanceService::class)->reportBreakdown($asset, $this->user, 'It will not start at all.');
        app('auth')->forgetGuards(); // the test case signs a user in; a real request starts clean
        foreach (['overview', 'work-orders', 'beds-to-clean', 'assets/'.$asset->tag] as $p) {
            $this->getJson("/api/v1/facility/$p")->assertUnauthorized();
        }
        $user = $this->actor(['facility.dashboard.view', 'facility.asset.view', 'facility.workorder.view', 'facility.housekeeping.view']);
        $h = ['Authorization' => 'Bearer '.$user->createToken('t')->plainTextToken, 'X-Organization-Id' => (string) $this->organization->id];
        $this->assertSame(1, $this->getJson('/api/v1/facility/overview', $h)->assertOk()->json('data.assets_down'));
        $r = $this->getJson('/api/v1/facility/assets/'.$asset->tag, $h)->assertOk();
        $this->assertSame([false, 'Out of service'], [$r->json('data.usable'), $r->json('data.reason')]);
        $this->assertSame('urgent', $this->getJson('/api/v1/facility/work-orders', $h)->assertOk()->json('data.0.priority'));
        $this->getJson('/api/v1/facility/beds-to-clean', $h)->assertOk();
        app('auth')->forgetGuards();
        $none = $this->actor(['facility.asset.view']);
        $this->getJson('/api/v1/facility/overview', ['Authorization' => 'Bearer '.$none->createToken('t')->plainTextToken, 'X-Organization-Id' => (string) $this->organization->id])->assertForbidden();
        app('auth')->forgetGuards();
        $other = Organization::factory()->create();
        $this->getJson('/api/v1/facility/overview', ['X-Organization-Id' => (string) $other->id] + $h)->assertForbidden();
        $this->getJson('/api/v1/facility/assets/NOPE', $h)->assertNotFound();
    }
}
