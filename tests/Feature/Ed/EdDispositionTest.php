<?php

namespace Tests\Feature\Ed;

use App\Models\Ed\EdBay;
use App\Models\Ed\EdDisposition;
use App\Models\Ed\EdProtocolRun;
use App\Models\Ed\EdVisit;
use App\Models\Icu\IcuAdmissionRequest;
use App\Models\Ipd\IpdAdmissionRequest;
use App\Models\Ot\OtSurgeryRequest;
use App\Models\User;
use App\Services\Ed\EdBayService;
use App\Services\Ed\EdBoardQuery;
use App\Services\Ed\EdDispositionService;
use App\Services\Ed\EdObservationService;
use App\Services\Ed\EdProtocolService;
use App\Services\Ed\EdVisitService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class EdDispositionTest extends EdTestCase
{
    private function visit(): EdVisit
    {
        return app(EdVisitService::class)->arrive($this->user, $this->branch, ['patient_id' => $this->patient()->id, 'arrival_mode' => 'walk_in']);
    }

    private function decide(EdVisit $visit, array $data, ?User $by = null): EdDisposition
    {
        return app(EdDispositionService::class)->decide($visit, $by ?? $this->user, $data);
    }

    public function test_discharge_closes_the_visit_frees_the_bay_and_leaves_the_board(): void
    {
        $visit = $this->visit();
        $bay = $this->bay();
        app(EdBayService::class)->assign($visit, $bay, $this->user);

        $this->decide($visit, ['type' => 'discharge']);

        $visit->refresh();
        $this->assertSame(EdVisit::DISPOSITIONED, $visit->status);
        $this->assertSame('discharge', $visit->disposition_type);
        $this->assertNull($visit->bay_id);
        $this->assertSame(EdBay::CLEANING, $bay->fresh()->status);
        $this->assertSame('completed', $visit->encounter->fresh()->status);
        $this->assertSame([], app(EdBoardQuery::class)->forBranch($this->organization->id, $this->branch->id)['visits']);
    }

    public function test_a_visit_can_only_be_closed_once(): void
    {
        $visit = $this->visit();
        $this->decide($visit, ['type' => 'discharge']);

        $this->expectException(ValidationException::class);
        $this->decide($visit, ['type' => 'lwbs']);
    }

    public function test_admission_icu_and_surgery_create_requests_in_the_receiving_modules(): void
    {
        $a = $this->visit();
        $b = $this->visit();
        $c = $this->visit();
        $this->decide($a, ['type' => 'admit_ipd', 'reason' => 'Pneumonia']);
        $this->decide($b, ['type' => 'icu', 'reason' => 'Ventilation']);
        $this->decide($c, ['type' => 'surgery', 'reason' => 'Appendicitis']);

        $this->assertSame(1, IpdAdmissionRequest::where('source_encounter_id', $a->encounter_id)->count());
        $this->assertSame(1, IcuAdmissionRequest::where('encounter_id', $b->encounter_id)->count());
        $this->assertSame(1, OtSurgeryRequest::where('encounter_id', $c->encounter_id)->count());
        $this->assertSame('admit_ipd', EdDisposition::where('visit_id', $a->id)->value('type'));
    }

    public function test_required_details_are_enforced_and_nothing_is_closed_on_failure(): void
    {
        $visit = $this->visit();
        foreach ([['type' => 'transfer', 'reason' => 'x'], ['type' => 'lama', 'reason' => 'x'], ['type' => 'admit_ipd'], ['type' => 'nonsense']] as $data) {
            try {
                $this->decide($visit, $data);
                $this->fail('accepted '.json_encode($data));
            } catch (ValidationException) {
            }
        }

        $this->assertTrue($visit->fresh()->isActive());
        $this->assertSame(0, EdDisposition::count());
    }

    public function test_referral_with_ambulance_is_recorded(): void
    {
        $visit = $this->visit();
        $d = $this->decide($visit, ['type' => 'transfer', 'destination' => 'Dhaka Medical', 'reason' => 'Needs neurosurgery', 'ambulance_required' => true]);

        $this->assertTrue($d->ambulance_required);
        $this->assertDatabaseHas('encounter_referrals', ['encounter_id' => $visit->encounter_id, 'referred_to' => 'Dhaka Medical']);
    }

    public function test_closing_ends_observation_billing_and_stops_protocols(): void
    {
        $visit = $this->visit();
        $stay = app(EdObservationService::class)->start($visit, $this->user);
        app(EdProtocolService::class)->start($visit, $this->user, 'sepsis');

        $this->decide($visit, ['type' => 'discharge']);

        $this->assertNotNull($stay->fresh()->ended_at);
        $this->assertSame(EdProtocolRun::CANCELLED, EdProtocolRun::first()->status);
        $this->assertSame(EdVisit::DISPOSITIONED, $visit->fresh()->status);
    }

    public function test_death_needs_the_permission_a_time_and_a_cause_and_marks_the_patient(): void
    {
        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $nurse = $this->staff(['charge_nurse']);
        $doctor = $this->staff(['doctor']);
        $visit = $this->visit();
        $data = ['type' => 'death', 'time_of_death' => now()->subMinutes(5)->toDateTimeString(), 'cause' => 'Cardiac arrest'];

        try {
            $this->decide($visit, $data, $nurse);
            $this->fail('a nurse declared death');
        } catch (ValidationException) {
        }
        $this->assertTrue($visit->fresh()->isActive());

        try {
            $this->decide($visit, ['type' => 'death', 'time_of_death' => now()->addHour()->toDateTimeString(), 'cause' => 'x'], $doctor);
            $this->fail('future time accepted');
        } catch (ValidationException) {
        }

        $this->decide($visit, $data, $doctor);
        $this->assertSame('deceased', $visit->patient->fresh()->status);
        $this->assertNotNull($visit->patient->fresh()->deceased_at);
    }

    public function test_lwbs_cancels_an_unseen_encounter(): void
    {
        $visit = $this->visit();
        $this->decide($visit, ['type' => 'lwbs']);
        $this->assertSame('cancelled', $visit->encounter->fresh()->status);
    }

    public function test_screen_closes_and_shows_the_outcome(): void
    {
        $visit = $this->visit();
        $base = "/admin/emergency/visits/{$visit->public_id}";
        $this->post("$base/disposition", ['type' => 'discharge', 'reason' => 'Minor sprain'])->assertRedirect();
        $this->get($base)->assertOk()->assertSee('Minor sprain');
        $this->post("$base/disposition", ['type' => 'discharge'])->assertSessionHasErrors('visit');
    }
}
