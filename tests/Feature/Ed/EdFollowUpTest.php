<?php

namespace Tests\Feature\Ed;

use App\Models\Billing\ChargeEventLog;
use App\Models\Clinical\ClinicalAlert;
use App\Models\Ed\EdConsult;
use App\Models\Ed\EdObservationStay;
use App\Models\Ed\EdVisit;
use App\Models\Organization;
use App\Services\Ed\EdConsultService;
use App\Services\Ed\EdObservationService;
use App\Services\Ed\EdTimerService;
use App\Services\Ed\EdVisitService;
use Illuminate\Validation\ValidationException;

class EdFollowUpTest extends EdTestCase
{
    private function visit(): EdVisit
    {
        return app(EdVisitService::class)->arrive($this->user, $this->branch, ['patient_id' => $this->patient()->id, 'arrival_mode' => 'walk_in']);
    }

    public function test_consult_due_time_follows_urgency_and_overdue_raises_one_alert(): void
    {
        $consult = app(EdConsultService::class)->request($this->visit(), $this->user, 'Cardiology', 'urgent');
        $this->assertSame(30, (int) $consult->requested_at->diffInMinutes($consult->due_at));

        app(EdTimerService::class)->check();
        $this->assertSame(0, ClinicalAlert::where('category', 'ed_consult_overdue')->count());

        $this->travel(31)->minutes();
        app(EdTimerService::class)->check();
        app(EdTimerService::class)->check();
        $this->assertSame(1, ClinicalAlert::where('category', 'ed_consult_overdue')->count());
    }

    public function test_an_answered_consult_never_alerts_and_cannot_be_answered_twice(): void
    {
        $service = app(EdConsultService::class);
        $consult = $service->request($this->visit(), $this->user, 'Surgery', 'emergent');
        $service->respond($consult, $this->user, 'Will come');
        $this->travel(60)->minutes();
        app(EdTimerService::class)->check();
        $this->assertSame(0, ClinicalAlert::where('category', 'ed_consult_overdue')->count());

        $this->expectException(ValidationException::class);
        $service->respond($consult, $this->user);
    }

    public function test_unknown_urgency_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        app(EdConsultService::class)->request($this->visit(), $this->user, 'Surgery', 'whenever');
    }

    public function test_billed_hours_follow_the_rule(): void
    {
        $service = app(EdObservationService::class);
        $this->assertSame(1, $service->billedHours(5));
        $this->assertSame(1, $service->billedHours(70));
        $this->assertSame(2, $service->billedHours(71));
        $this->assertSame(2, $service->billedHours(130));
        $this->assertSame(3, $service->billedHours(131));
    }

    public function test_observation_moves_the_visit_and_ending_bills_once(): void
    {
        $visit = $this->visit();
        $service = app(EdObservationService::class);
        $stay = $service->start($visit, $this->user);
        $this->assertSame(EdVisit::OBSERVATION, $visit->fresh()->status);

        $this->travel(3)->hours();
        $service->end($stay, $this->user);
        $this->assertSame(EdVisit::AWAITING_DISPOSITION, $visit->fresh()->status);
        $this->assertSame(3, $stay->fresh()->billed_hours);
        $this->assertSame(1, ChargeEventLog::where('idempotency_key', 'like', 'ED:%:ED-OBS-HR:%')->count());

        $this->expectException(ValidationException::class);
        $service->end($stay, $this->user);
    }

    public function test_one_open_stay_at_a_time_and_long_stays_alert(): void
    {
        $visit = $this->visit();
        $service = app(EdObservationService::class);
        $service->start($visit, $this->user);
        try {
            $service->start($visit, $this->user);
            $this->fail('second stay accepted');
        } catch (ValidationException) {
        }

        $this->travel(25)->hours();
        app(EdTimerService::class)->check();
        $this->assertSame(1, ClinicalAlert::where('category', 'ed_observation_long')->count());
        $this->assertSame(1, EdObservationStay::count());
    }

    public function test_screens_work_and_show_results(): void
    {
        $visit = $this->visit();
        $base = "/admin/emergency/visits/{$visit->public_id}";
        $this->post("$base/orders", ['order_type' => 'laboratory', 'items' => [['name' => 'CBC']]])->assertRedirect();
        $this->post("$base/consults", ['specialty' => 'Neurology', 'urgency' => 'routine'])->assertRedirect();
        $this->post('/admin/emergency/consults/'.EdConsult::firstOrFail()->public_id.'/respond', ['response_note' => 'Seen'])->assertRedirect();
        $this->post("$base/observation")->assertRedirect();
        $this->get($base)->assertOk()->assertSee('CBC')->assertSee('Neurology')->assertSee('In observation since');
        $this->post('/admin/emergency/observation/'.EdObservationStay::firstOrFail()->public_id.'/end')->assertRedirect();
        $this->assertNotNull(EdObservationStay::first()->ended_at);
    }

    public function test_other_organization_consult_is_404(): void
    {
        $visit = $this->visit();
        $consult = app(EdConsultService::class)->request($visit, $this->user, 'ENT', 'routine');
        $visit->update(['organization_id' => Organization::factory()->create()->id]);
        $this->post('/admin/emergency/consults/'.$consult->public_id.'/respond')->assertNotFound();
    }
}
