<?php

namespace Tests\Feature\Mch;

use App\Models\Mch\MchPregnancy;
use App\Models\Organization;
use App\Models\Patient;
use App\Services\Mch\ImmunisationService;
use App\Services\Mch\MchOverview;
use App\Services\Mch\PregnancyService;
use App\Support\Dashboard\DashboardPeriod;
use Carbon\CarbonImmutable;
use Tests\Feature\Vital\VitalTestCase;

class DeskTest extends VitalTestCase
{
    private function svc(): PregnancyService
    {
        return app(PregnancyService::class);
    }

    public function test_every_screen_needs_its_own_right(): void
    {
        $nobody = $this->actor(['patients.view']);
        foreach (['/admin/mch', '/admin/mch/pregnancies', '/admin/mch/pregnancies/new', '/admin/mch/immunisation'] as $url) {
            $this->as($nobody)->get($url)->assertForbidden();
        }
        $this->as($this->actor(['mch.immunisation.view']))->get('/admin/mch/pregnancies')->assertForbidden();
        $this->as($this->actor(['mch.pregnancy.view']))->get('/admin/mch/immunisation')->assertForbidden();
    }

    public function test_a_pregnancy_is_booked_visited_and_delivered_from_the_screens(): void
    {
        $midwife = $this->actor(['mch.pregnancy.view', 'mch.pregnancy.book', 'mch.anc.record', 'mch.delivery.record', 'vital.birth.view']);
        $woman = $this->mother();

        $this->as($midwife)->post('/admin/mch/pregnancies', ['patient_id' => $woman->id, 'lmp' => now()->subWeeks(30)->toDateString(), 'gravida' => 1, 'para' => 0, 'risk_factors' => ['twins']])->assertRedirect();
        $p = MchPregnancy::firstOrFail();
        $this->as($midwife)->post("/admin/mch/pregnancies/{$p->public_id}/visits", ['bp_systolic' => 160, 'bp_diastolic' => 100])->assertSessionHas('warning');
        $this->as($midwife)->get("/admin/mch/pregnancies/{$p->public_id}")->assertOk()->assertSee('Bp Severe')->assertSee('Twins');
        $this->as($midwife)->post("/admin/mch/pregnancies/{$p->public_id}/delivery", ['mode' => 'normal', 'babies' => [['sex' => 'female', 'weight_g' => 3000]]])->assertSessionHas('success');

        $this->assertSame('delivered', $p->refresh()->status);
    }

    public function test_a_nurse_may_record_visits_but_not_book_or_deliver(): void
    {
        $p = $this->svc()->book($this->mother(), ['lmp' => now()->subWeeks(12)->toDateString()], $this->actor());
        $nurse = $this->actor(['mch.pregnancy.view', 'mch.anc.record']);

        $this->as($nurse)->post("/admin/mch/pregnancies/{$p->public_id}/visits", ['bp_systolic' => 110, 'bp_diastolic' => 70])->assertSessionHas('success');
        $this->as($nurse)->post("/admin/mch/pregnancies/{$p->public_id}/delivery", ['mode' => 'normal', 'babies' => [['sex' => 'female']]])->assertForbidden();
        $this->as($nurse)->get('/admin/mch/pregnancies/new')->assertForbidden();
    }

    public function test_another_hospitals_pregnancy_is_not_found(): void
    {
        $other = Organization::factory()->create();
        $theirs = MchPregnancy::factory()->create(['organization_id' => $other->id, 'patient_id' => Patient::factory()->create(['organization_id' => $other->id])->id]);

        $this->as($this->actor(['mch.pregnancy.view']))->get("/admin/mch/pregnancies/{$theirs->public_id}")->assertNotFound();
    }

    public function test_vaccines_are_given_from_the_card_page_and_a_second_try_is_refused(): void
    {
        $nurse = $this->actor(['mch.immunisation.view', 'mch.immunisation.give']);
        $child = $this->patient(['date_of_birth' => now()->subDays(2)->toDateString()]);

        $this->as($nurse)->get("/admin/mch/immunisation/{$child->id}")->assertOk()->assertSee('Due now');
        $this->as($nurse)->post("/admin/mch/immunisation/{$child->id}", ['vaccine' => 'bcg', 'batch_no' => 'B9'])->assertSessionHas('success');
        $this->as($nurse)->post("/admin/mch/immunisation/{$child->id}", ['vaccine' => 'bcg', 'batch_no' => 'B9'])->assertSessionHasErrors('vaccine');
        $this->as($this->actor(['mch.immunisation.view']))->post("/admin/mch/immunisation/{$child->id}", ['vaccine' => 'opv0', 'batch_no' => 'B9'])->assertForbidden();
    }

    public function test_the_overview_counts_the_women_and_children_who_need_attention(): void
    {
        $now = CarbonImmutable::now();
        $by = $this->actor();
        $risky = $this->svc()->book($this->mother(), ['lmp' => now()->subWeeks(36)->toDateString(), 'risk_factors' => ['diabetes']], $by);   // due in 4 weeks, high risk
        $this->svc()->recordVisit($risky, ['bp_systolic' => 120, 'bp_diastolic' => 80], $by);
        $this->svc()->book($this->mother(), ['lmp' => now()->subWeeks(10)->toDateString()], $by);                                      // never seen: booked just now, so not stale
        $late = $this->svc()->book($this->mother(), ['lmp' => now()->subWeeks(42)->toDateString()], $by);                              // past the date
        $done = $this->svc()->book($this->mother(), ['lmp' => now()->subWeeks(38)->toDateString()], $by);
        $this->svc()->deliver($done, ['mode' => 'caesarean'], [['sex' => 'female', 'weight_g' => 2200]], $by);
        $this->patient(['date_of_birth' => now()->subDays(100)->toDateString()]);
        $stranger = Organization::factory()->create();
        MchPregnancy::factory()->create(['organization_id' => $stranger->id, 'patient_id' => Patient::factory()->create(['organization_id' => $stranger->id])->id]);

        $s = app(MchOverview::class)->summary($this->organization->id, null, DashboardPeriod::make('30d', now: $now), $now);

        $this->assertSame([3, 1, 1, 1], [$s['pregnant'], $s['high_risk'], $s['due_30'], $s['past_date']]);
        $this->assertSame([1, 100.0, 100.0], [$s['current']['deliveries'], $s['current']['caesarean_percent'], $s['current']['low_weight_percent']]);
        $this->assertGreaterThanOrEqual(1, $s['children_due']);
        $this->assertNotNull($late->id + app(ImmunisationService::class)->schedule()['bcg'][1]);
    }

    public function test_the_overview_page_states_its_question(): void
    {
        $html = $this->as($this->actor(['mch.dashboard.view']))->get('/admin/mch')->assertOk()->getContent();

        $this->assertStringContainsString('data-dashboard-question', $html);
        $this->assertStringContainsString('High-risk pregnancies', $html);
    }
}
