<?php

namespace Tests\Feature\Vital;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\Vital\VrBirthNotification;
use App\Models\Vital\VrDeathCertification;
use App\Models\Vital\VrMedicolegalCase;
use App\Services\Vital\BirthService;
use App\Services\Vital\DeathService;
use App\Services\Vital\MedicoLegalService;
use App\Services\Vital\VitalOverview;
use App\Support\Dashboard\DashboardPeriod;
use Carbon\CarbonImmutable;

/** The screens: who may open them, what they show, and the overview's figures. */
class DeskTest extends VitalTestCase
{
    public function test_every_screen_needs_its_own_right(): void
    {
        $nobody = $this->actor(['patients.view']);
        foreach (['/vital', '/vital/births', '/vital/births/new', '/vital/deaths', '/vital/deaths/new', '/vital/police-cases', '/vital/police-cases/new'] as $url) {
            $this->as($nobody)->get($url)->assertForbidden();
        }
        $this->as($this->actor(['vital.birth.view']))->get('/vital/deaths')->assertForbidden();
        $this->as($this->actor(['vital.death.view']))->get('/vital/police-cases')->assertForbidden();
    }

    public function test_a_birth_is_notified_from_the_screen_and_a_second_person_issues_it(): void
    {
        $clerk = $this->actor(['vital.birth.view', 'vital.birth.create', 'vital.birth.issue']);
        $mother = $this->mother();

        $this->as($clerk)->post('/vital/births', ['mother_patient_id' => $mother->id, 'sex' => 'female', 'outcome' => 'live_birth', 'born_at' => now()->subHours(3)->format('Y-m-d\TH:i'), 'place_of_birth' => 'this_hospital'])->assertRedirect();
        $n = VrBirthNotification::firstOrFail();
        $this->as($clerk)->get('/vital/births/'.$n->public_id)->assertOk()->assertDontSee('Check and issue');
        $this->as($clerk)->post("/vital/births/{$n->public_id}/issue", ['issued_to' => 'Father'])->assertSessionHasErrors('status');

        $this->as($this->actor(['vital.birth.view', 'vital.birth.issue']))->post("/vital/births/{$n->public_id}/issue", ['issued_to' => 'Father'])->assertRedirect();
        $this->assertSame('issued', $n->refresh()->status);
    }

    public function test_a_doctor_certifies_and_a_second_person_issues_and_the_writer_may_not_issue_what_they_certified(): void
    {
        $patient = $this->patient(['deceased_at' => now()->subHours(6)]);
        $writer = $this->actor(['vital.death.view', 'vital.death.create']);
        $doctor = $this->actor(['vital.death.view', 'vital.death.certify', 'vital.death.issue']);

        $this->as($writer)->post('/vital/deaths', ['patient_id' => $patient->id, 'died_at' => now()->subHours(6)->format('Y-m-d\TH:i'), 'place_of_death' => 'this_hospital', 'manner' => 'natural', 'cause_a' => 'Septic shock'])->assertRedirect();
        $c = VrDeathCertification::firstOrFail();
        $this->as($writer)->post("/vital/deaths/{$c->public_id}/certify")->assertForbidden();
        $this->as($doctor)->post("/vital/deaths/{$c->public_id}/certify", ['registration_no' => 'A-1'])->assertRedirect();
        $this->as($doctor)->post("/vital/deaths/{$c->public_id}/issue", ['issued_to' => 'Son', 'issued_relation' => 'Son', 'issued_id_type' => 'nid', 'issued_id_number' => '123456789'])->assertSessionHasErrors('status');

        $this->as($this->actor(['vital.death.view', 'vital.death.issue']))->post("/vital/deaths/{$c->public_id}/issue", ['issued_to' => 'Son', 'issued_relation' => 'Son', 'issued_id_type' => 'nid', 'issued_id_number' => '123456789'])->assertRedirect();
        $this->assertSame('issued', $c->refresh()->status);
    }

    public function test_the_police_case_is_hidden_from_a_certifier_who_may_not_read_it(): void
    {
        $c = app(DeathService::class)->draft($this->patient(), ['died_at' => now()->subHours(4)->toDateTimeString(), 'manner' => 'accident', 'cause_a' => 'Head injury'], $this->actor());

        $without = $this->as($this->actor(['vital.death.view']))->get('/vital/deaths/'.$c->public_id)->assertOk()->getContent();
        $with = $this->as($this->actor(['vital.death.view', 'vital.medicolegal.view']))->get('/vital/deaths/'.$c->public_id)->assertOk()->getContent();

        $this->assertStringNotContainsString('/vital/police-cases/', $without);
        $this->assertStringContainsString('/vital/police-cases/', $with);
    }

    public function test_what_happened_is_for_people_with_the_medico_legal_right_only(): void
    {
        $case = app(MedicoLegalService::class)->register($this->patient(), ['kind' => 'assault', 'brought_at' => now()->subHour()->toDateTimeString(), 'description' => 'SECRET-DETAIL of the assault.'], $this->actor());

        $this->as($this->actor(['vital.dashboard.view', 'vital.death.view']))->get('/vital/police-cases/'.$case->public_id)->assertForbidden();
        $this->as($this->actor(['vital.medicolegal.view']))->get('/vital/police-cases/'.$case->public_id)->assertOk()->assertSee('SECRET-DETAIL');
        $other = Organization::factory()->create();
        $theirs = VrMedicolegalCase::factory()->create(['organization_id' => $other->id, 'patient_id' => Patient::factory()->create(['organization_id' => $other->id])->id]);
        $this->as($this->actor(['vital.medicolegal.view']))->get('/vital/police-cases/'.$theirs->public_id)->assertNotFound();
    }

    public function test_the_overview_counts_what_is_waiting_and_only_this_hospital(): void
    {
        $now = CarbonImmutable::now();
        $svc = app(DeathService::class);
        $this->patient(['deceased_at' => now()->subHours(30)]);
        $certified = $svc->certify($svc->draft($this->patient(), ['died_at' => now()->subHours(10)->toDateTimeString(), 'manner' => 'natural', 'cause_a' => 'MI'], $this->actor()), $this->actor());
        $held = $svc->certify($svc->draft($this->patient(), ['died_at' => now()->subHours(9)->toDateTimeString(), 'manner' => 'accident', 'cause_a' => 'Head injury'], $this->actor()), $this->actor());
        $b = app(BirthService::class);
        $b->draft($this->mother(), $this->birth(), $this->actor());
        $b->draft($this->mother(), $this->birth(['outcome' => 'stillbirth', 'gestation_weeks' => 32]), $this->actor());
        $stranger = Organization::factory()->create();
        VrBirthNotification::factory()->create(['organization_id' => $stranger->id, 'mother_patient_id' => Patient::factory()->create(['organization_id' => $stranger->id])->id, 'born_at' => now()->subHour()]);

        $s = app(VitalOverview::class)->summary($this->organization->id, null, DashboardPeriod::make('30d', now: $now), $now);

        $this->assertSame([1, 30], [$s['awaiting_certificate'], $s['oldest_awaiting_hours']], 'one death still has no certificate, for 30 hours');
        $this->assertSame([2, 1, 1], [$s['certified_not_issued'], $s['held_by_police'], $s['police_cases_open']]);
        $this->assertSame([1, 1, 2], [$s['current']['births'], $s['current']['stillbirths'], $s['current']['deaths']]);
        $this->assertSame(1, $s['births_waiting_for_issue'] - 1, 'two drafts; one is the stillbirth');
        $this->assertEqualsCanonicalizing(['Natural', 'Accident'], array_column($s['by_manner'], 'manner'));
        $this->assertNotNull($certified->id + $held->id);
    }

    public function test_the_overview_page_states_its_question_and_its_time(): void
    {
        $html = $this->as($this->actor(['vital.dashboard.view']))->get('/vital')->assertOk()->getContent();

        $this->assertStringContainsString('data-dashboard-question', $html);
        $this->assertStringContainsString('data-dashboard-asof', $html);
        $this->assertStringContainsString('Deaths without a certificate', $html);
    }

    public function test_the_death_list_offers_to_write_a_certificate_for_each_death_waiting(): void
    {
        $p = $this->patient(['deceased_at' => now()->subHours(3)]);

        $html = $this->as($this->actor(['vital.death.view', 'vital.death.create']))->get('/vital/deaths')->assertOk()->getContent();

        $this->assertStringContainsString('Deaths with no certificate yet', $html);
        $this->assertStringContainsString('patient_id='.$p->id, $html);
    }
}
