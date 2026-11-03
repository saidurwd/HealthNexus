<?php

namespace Tests\Feature\Welfare;

use App\Models\Billing\BillingAdjustment;
use App\Models\Organization;
use App\Models\User;
use App\Models\Welfare\WfAssessment;
use App\Models\Welfare\WfFund;
use App\Models\Welfare\WfGrant;
use App\Services\Billing\AdjustmentService;
use App\Services\TenantContextResolver;
use App\Services\Welfare\FundService;
use App\Services\Welfare\WelfareCheck;
use App\Services\Welfare\WelfareInstaller;

class DeskTest extends WelfareTestCase
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
        app(WelfareInstaller::class)->install($this->organization->id);
        $fund = WfFund::query()->first();
        $this->as($this->user);
        foreach (['', '/assessments', '/grants', '/funds', '/funds/'.$fund->public_id, '/reports'] as $p) {
            $this->get('/welfare'.$p)->assertOk();
        }
        $this->as($this->actor([]));
        foreach (['', '/assessments', '/grants', '/funds', '/funds/'.$fund->public_id, '/reports'] as $p) {
            $this->get('/welfare'.$p)->assertForbidden();
        }
    }

    public function test_the_whole_journey_through_the_screens_with_a_second_person_at_each_decision(): void
    {
        $patient = $this->patient();
        $fund = $this->fundWith('20000');
        $assessor = $this->actor(['welfare.dashboard.view', 'welfare.assessment.view', 'welfare.assessment.create']);
        $committee = $this->actor(['welfare.dashboard.view', 'welfare.assessment.view', 'welfare.assessment.decide', 'welfare.grant.view', 'welfare.grant.approve']);
        $officer = $this->actor(['welfare.dashboard.view', 'welfare.assessment.view', 'welfare.grant.view', 'welfare.grant.request', 'welfare.grant.apply']);

        $this->as($assessor)->post('/welfare/assessments', ['patient_no' => $patient->enterprise_patient_no, 'monthly_income' => 3000, 'household_size' => 5, 'factors' => ['no_earner'], 'evidence_note' => 'Visited the house, father lost his job.'])->assertRedirect();
        $a = WfAssessment::query()->first();
        $this->assertSame('free', $a->category);
        $this->as($assessor)->post('/welfare/assessments/'.$a->public_id.'/submit')->assertSessionHas('success');
        $this->as($assessor)->post('/welfare/assessments/'.$a->public_id.'/decide', ['decision' => 'approve'])->assertForbidden();
        $this->as($committee)->post('/welfare/assessments/'.$a->public_id.'/decide', ['decision' => 'approve', 'note' => 'Agreed'])->assertSessionHas('success');

        $this->as($officer)->post('/welfare/assessments/'.$a->public_id.'/grants', ['fund_id' => $fund->id, 'cap_amount' => 6000, 'purpose' => 'Delivery'])->assertRedirect();
        $g = WfGrant::query()->first();
        $this->as($officer)->post('/welfare/grants/'.$g->public_id.'/approve')->assertForbidden();
        $this->as($committee)->post('/welfare/grants/'.$g->public_id.'/approve', ['note' => 'Fine'])->assertSessionHas('success');

        $invoice = $this->bill($patient, '4000.00');
        $this->as($officer)->post('/welfare/grants/'.$g->public_id.'/apply', ['invoice_id' => $invoice->id])->assertSessionHas('success');
        $this->as($officer)->get('/welfare/grants/'.$g->public_id)->assertOk()->assertSee('Waiting for Billing');
        $adj = BillingAdjustment::query()->first();
        app(AdjustmentService::class)->approve($adj, $this->actor([]));
        $this->as($committee)->get('/welfare/grants/'.$g->public_id)->assertOk()->assertSee('4,000.00');
        $this->assertSame('16000.00', app(FundService::class)->balance($fund));
    }

    public function test_income_and_circumstances_are_only_for_those_with_the_assessment_right(): void
    {
        $a = $this->approvedAssessment(null, ['monthly_income' => 1234, 'household_size' => 2]);
        $reportsOnly = $this->actor(['welfare.dashboard.view', 'welfare.report.view']);

        $this->as($reportsOnly)->get('/welfare/assessments/'.$a->public_id)->assertForbidden();
        $html = $this->as($reportsOnly)->get('/welfare/reports')->assertOk()->getContent();
        $this->assertStringNotContainsString('1,234', $html);
        $this->assertStringNotContainsString((string) $a->patient->display_name, $html);
    }

    public function test_another_hospitals_records_do_not_exist(): void
    {
        $other = Organization::factory()->create();
        $foreign = WfFund::factory()->create(['organization_id' => $other->id]);
        $this->as($this->user)->get('/welfare/funds/'.$foreign->public_id)->assertNotFound();
    }

    public function test_the_api_needs_a_login_the_right_and_the_hospital_and_carries_no_patient_data(): void
    {
        $fund = $this->fundWith('1000');
        $reader = $this->actor(['welfare.dashboard.view', 'welfare.fund.view']);
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/welfare/overview')->assertUnauthorized();
        $this->actingAs($reader, 'sanctum');
        $this->getJson('/api/v1/welfare/overview', ['X-Organization-Id' => $this->organization->id])->assertOk()->assertJsonStructure(['success', 'data' => ['total_available', 'given', 'patients_helped'], 'meta']);
        $res = $this->getJson('/api/v1/welfare/funds', ['X-Organization-Id' => $this->organization->id])->assertOk();
        $this->assertSame('1000.00', $res->json('data.0.available'));
        $this->assertStringNotContainsString('income', strtolower($res->getContent()));
        $this->actingAs($this->actor([]), 'sanctum');
        $this->getJson('/api/v1/welfare/funds', ['X-Organization-Id' => $this->organization->id])->assertForbidden();
    }

    public function test_the_daily_check_raises_what_waits_and_what_runs_low_and_lapses_the_old(): void
    {
        $fund = $this->fundWith('1000', ['low_balance_alert' => 5000]);
        $out = app(WelfareCheck::class)->run($this->organization->id);
        $this->assertSame(1, $out['funds_low']);
        $this->assertSame(['assessments_expired' => 0, 'grants_expired' => 0, 'settled' => 0], array_intersect_key($out, array_flip(['assessments_expired', 'grants_expired', 'settled'])));
        $this->assertSame(1, app(WelfareCheck::class)->runAll());
        $this->artisan('welfare:check')->assertSuccessful();
        $this->artisan('welfare:install')->assertSuccessful();
        $this->assertGreaterThanOrEqual(3, WfFund::query()->where('organization_id', $this->organization->id)->count());
        $this->assertNotNull($fund->fresh());
    }
}
