<?php

namespace Tests\Feature\Ed;

use App\Models\Clinical\ClinicalAlert;
use App\Models\Ed\EdResusEvent;
use App\Models\Ed\EdVisit;
use App\Models\Organization;
use App\Services\Ed\EdMonitoringService;
use App\Services\Ed\EdResuscitationService;
use App\Services\Ed\EdVisitService;
use Illuminate\Validation\ValidationException;

class EdResusTest extends EdTestCase
{
    private function visit(): EdVisit
    {
        return app(EdVisitService::class)->arrive($this->user, $this->branch, ['patient_id' => $this->patient()->id, 'arrival_mode' => 'ambulance']);
    }

    public function test_events_are_added_to_the_timeline_in_order(): void
    {
        $visit = $this->visit();
        $service = app(EdResuscitationService::class);
        $service->record($visit, $this->user, 'cpr_started');
        $service->record($visit, $this->user, 'adrenaline_given', '1 mg');

        $this->assertSame(['cpr_started', 'adrenaline_given'], $visit->resusEvents()->pluck('event_type')->all());
    }

    public function test_unknown_event_and_closed_visit_are_refused(): void
    {
        $visit = $this->visit();
        $this->expectException(ValidationException::class);
        app(EdResuscitationService::class)->record($visit, $this->user, 'made_up');
    }

    public function test_closed_visit_cannot_take_events(): void
    {
        $visit = $this->visit();
        $visit->update(['status' => EdVisit::DISPOSITIONED]);
        $this->expectException(ValidationException::class);
        app(EdResuscitationService::class)->record($visit, $this->user, 'note');
    }

    public function test_code_blue_records_an_event_and_one_alert_per_minute(): void
    {
        $visit = $this->visit();
        $service = app(EdResuscitationService::class);
        $service->callCodeBlue($visit, $this->user, 'Bay A1');
        $service->callCodeBlue($visit, $this->user, 'Bay A1');

        $this->assertSame(2, EdResusEvent::where('visit_id', $visit->id)->count());
        $this->assertSame(1, ClinicalAlert::where('category', 'code_blue')->count());
    }

    public function test_monitoring_writes_observations_and_alerts_on_deterioration(): void
    {
        $visit = $this->visit();
        $breaches = app(EdMonitoringService::class)->record($visit, $this->user, ['pulse' => 150, 'oxygen_saturation' => 97]);

        $this->assertCount(1, $breaches);
        $this->assertSame(1, ClinicalAlert::where('category', 'ed_deterioration')->count());
        $this->assertDatabaseHas('clinical_observations', ['patient_id' => $visit->patient_id, 'source_module' => 'ed']);
    }

    public function test_normal_readings_raise_no_alert_and_empty_input_is_refused(): void
    {
        $visit = $this->visit();
        $this->assertSame([], app(EdMonitoringService::class)->record($visit, $this->user, ['pulse' => 80]));
        $this->assertSame(0, ClinicalAlert::where('category', 'ed_deterioration')->count());

        $this->expectException(ValidationException::class);
        app(EdMonitoringService::class)->record($visit, $this->user, ['pulse' => null]);
    }

    public function test_screens_post_and_other_branch_is_404(): void
    {
        $visit = $this->visit();
        $this->post("/admin/emergency/visits/{$visit->public_id}/resus", ['event_type' => 'cpr_started'])->assertRedirect();
        $this->post("/admin/emergency/visits/{$visit->public_id}/monitor", ['vitals' => ['pulse' => 150]])->assertRedirect()->assertSessionHas('warning');
        $this->get("/admin/emergency/visits/{$visit->public_id}")->assertOk()->assertSee('Cpr Started');

        $other = $this->staff(['super_admin']);
        $visit->update(['organization_id' => Organization::factory()->create()->id]);
        $this->post("/admin/emergency/visits/{$visit->public_id}/resus", ['event_type' => 'note'])->assertNotFound();
    }
}
