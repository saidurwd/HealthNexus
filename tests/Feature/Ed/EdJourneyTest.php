<?php

namespace Tests\Feature\Ed;

use App\Models\Ed\EdBay;
use App\Models\Ed\EdVisit;
use App\Models\Ipd\IpdAdmissionRequest;

/** ED journey 14: an unknown, critically ill patient from arrival to admission, entirely through the screens. */
class EdJourneyTest extends EdTestCase
{
    public function test_unknown_patient_from_arrival_to_admission(): void
    {
        $bay = $this->bay('R1', 'RESUS');

        $this->post('/admin/emergency/arrivals', ['arrival_mode' => 'ambulance', 'sex' => 'M', 'estimated_age' => 45, 'description' => 'Collapsed in the market'])->assertRedirect();
        $visit = EdVisit::firstOrFail();
        $this->assertTrue($visit->is_unknown_patient);
        $base = "/admin/emergency/visits/{$visit->public_id}";

        $this->post("$base/triage", ['level' => 1, 'complaint' => 'Collapse', 'vitals' => ['pulse' => 38, 'oxygen_saturation' => 82]])->assertRedirect();
        $this->post("$base/bay", ['bay' => $bay->public_id])->assertRedirect();
        $this->post("$base/start")->assertRedirect();
        $this->post("$base/resus", ['event_type' => 'code_blue_called', 'detail' => 'Bay R1'])->assertRedirect();
        $this->post("$base/orders", ['order_type' => 'laboratory', 'items' => [['name' => 'Troponin']]])->assertRedirect();
        $this->post("$base/protocols", ['protocol' => 'acs'])->assertRedirect();
        $this->post("$base/disposition", ['type' => 'icu', 'reason' => 'Post-arrest care'])->assertRedirect();

        $visit->refresh();
        $this->assertSame(EdVisit::DISPOSITIONED, $visit->status);
        $this->assertSame(EdBay::CLEANING, $bay->fresh()->status);
        $this->assertNotNull($visit->doctor_seen_at);
        $this->assertDatabaseHas('icu_admission_requests', ['encounter_id' => $visit->encounter_id, 'is_emergency' => true]);
        $this->assertDatabaseHas('clinical_alerts', ['category' => 'code_blue']);
        $this->assertDatabaseHas('clinical_alerts', ['category' => 'ed_resuscitation']);
        $this->assertSame(0, IpdAdmissionRequest::count());
        $this->get('/admin/emergency')->assertOk();
        $this->getJson('/admin/emergency/board/data')->assertOk()->assertJsonPath('board.visits', []);
    }
}
