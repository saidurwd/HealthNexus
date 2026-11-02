<?php

namespace Tests\Feature\Welfare;

use App\Models\Billing\BillingAdjustment;
use App\Models\Organization;
use App\Models\User;
use App\Models\Welfare\WfFund;
use App\Models\Welfare\WfFundEntry;
use App\Services\Billing\AdjustmentService;
use App\Services\TenantContextResolver;
use App\Services\Welfare\ApplicationService;
use App\Services\Welfare\AssessmentService;
use App\Services\Welfare\FundService;
use App\Support\Dashboard\Numbers;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Dashboard\DashboardAcceptance;

/** The welfare dashboard against the dashboard standard: it states itself, refuses without the right, every figure opens a list that adds up, and nothing from another hospital is counted. */
class DashboardTest extends WelfareTestCase
{
    use DashboardAcceptance;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function as(User $user): static
    {
        $this->actingAs($user);
        session()->put(['tenant_organization_id' => $this->organization->id, 'tenant_branch_id' => $this->branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);

        return $this;
    }

    private function viewer(array $extra = []): User
    {
        return $this->actor(array_merge(['welfare.dashboard.view', 'welfare.fund.view', 'welfare.grant.view', 'welfare.assessment.view'], $extra));
    }

    private function giveAndApprove(string $amount): void
    {
        $patient = $this->patient(['first_name' => 'Zubaida', 'last_name' => 'Khatun']);
        $fund = $this->fundWith('50000');
        $g = $this->approvedGrant($fund, $patient, '20000.00');
        $app = app(ApplicationService::class)->apply($g, $this->bill($patient, $amount), null, $this->actor([]));
        app(AdjustmentService::class)->approve(BillingAdjustment::find($app->adjustment_id), $this->actor([]));
    }

    public function test_it_states_its_question_and_when_the_figures_were_worked_out(): void
    {
        $html = $this->assertDashboardStatesItself('/admin/welfare', $this->viewer());
        $this->assertStringContainsString('Is there enough free money', $html);
        $this->assertStringContainsString('Last 30 days', $html);
    }

    public function test_it_is_refused_without_the_right(): void
    {
        $this->assertDashboardRefuses('/admin/welfare', $this->actor(['welfare.fund.view']));
    }

    public function test_an_empty_hospital_gets_honest_empty_states_and_zero_is_a_real_zero(): void
    {
        $html = $this->assertDashboardStatesItself('/admin/welfare', $this->viewer());
        $this->assertStringContainsString('No funds yet.', $html);
        $this->assertStringContainsString('Nothing was given in this period.', $html);
        $this->assertStringContainsString('৳ 0', $html);
    }

    public function test_each_figure_opens_a_list_that_adds_up_to_the_same_figure(): void
    {
        $this->giveAndApprove('4000.00');
        $this->giveAndApprove('1500.00');
        $viewer = $this->viewer();
        $html = $this->as($viewer)->get('/admin/welfare')->assertOk()->getContent();

        $given = $this->assertTileEqualsList($html, 'Given to patients', $viewer, '5500.00', 2);
        $this->assertStringContainsString(Numbers::value($given['total'], 'taka'), $html, 'the tile shows the list\'s total');
        $received = $this->assertTileEqualsList($html, 'Received', $viewer, '100000.00', 2);
        $this->assertStringContainsString(Numbers::value($received['total'], 'taka'), $html);
        $this->assertTileEqualsList($html, 'Patients helped', $viewer, null, 2);
    }

    public function test_free_money_on_the_tile_is_the_free_money_in_the_funds_list(): void
    {
        $this->fundWith('7000');
        $this->fundWith('3000');
        $html = $this->as($this->viewer())->get('/admin/welfare')->assertOk()->getContent();
        $list = $this->as($this->viewer())->get($this->tileLink($html, 'Free to give now'))->assertOk()->getContent();

        $this->assertStringContainsString('৳ 10,000', $html);
        $this->assertStringContainsString('7,000.00', $list);
        $this->assertStringContainsString('3,000.00', $list);
    }

    public function test_the_waiting_counts_open_the_lists_they_count(): void
    {
        $assessor = $this->actor([]);
        $svc = app(AssessmentService::class);
        foreach ([1, 2] as $i) {
            $a = $svc->start($this->patient(), ['monthly_income' => 1000, 'household_size' => 2, 'evidence_note' => 'Visited the home and saw the situation.'], $assessor);
            $svc->submit($a, $assessor);
        }
        $viewer = $this->viewer();
        $html = $this->as($viewer)->get('/admin/welfare')->assertOk()->getContent();
        $list = $this->as($viewer)->get($this->tileLink($html, 'Assessments waiting'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($list, 'WA-'), 'two rows in the list the tile opens');
        $this->assertStringContainsString('status=submitted', $html);
    }

    public function test_a_change_against_the_period_before_is_shown_with_its_sign(): void
    {
        $fund = $this->fund();
        $svc = app(FundService::class);
        $svc->recordDonation($fund, '1000', null, $this->actor([]), null, null, now()->subDays(45)); // the 30 days before the last 30
        $svc->recordDonation($fund, '1500', null, $this->actor([]), null, null, now()->subDays(5));
        $html = $this->as($this->viewer())->get('/admin/welfare')->assertOk()->getContent();

        $this->assertStringContainsString('+50%', $html);
        $this->assertStringContainsString('▲', $html);
        $this->assertStringContainsString('vs the period before', $html);
    }

    public function test_nothing_from_another_hospital_is_counted_or_listed(): void
    {
        $other = Organization::factory()->create();
        $theirs = WfFund::factory()->create(['organization_id' => $other->id]);
        WfFundEntry::factory()->create(['organization_id' => $other->id, 'fund_id' => $theirs->id, 'amount' => 999999, 'occurred_at' => now()]);
        $this->fundWith('1000');
        $viewer = $this->viewer();
        $html = $this->as($viewer)->get('/admin/welfare')->assertOk()->getContent();

        $this->assertStringNotContainsString('999,999', $html);
        $this->assertTileEqualsList($html, 'Received', $viewer, '1000.00', 1);
        $this->assertStringNotContainsString('999,999', $this->as($viewer)->get('/admin/welfare/gifts')->getContent());
    }

    public function test_a_person_without_the_list_rights_sees_the_figures_but_no_links(): void
    {
        $this->fundWith('1000');
        $html = $this->as($this->actor(['welfare.dashboard.view']))->get('/admin/welfare')->assertOk()->getContent();

        $this->assertStringContainsString('৳ 1,000', $html);
        $this->assertNull($this->tileLink($html, 'Received'), 'a figure never links to a page the person may not open');
        $this->assertNull($this->tileLink($html, 'Given to patients'));
    }

    public function test_the_management_page_carries_no_patient_names(): void
    {
        $this->giveAndApprove('2000.00');
        $this->assertNoPatientIdentity($this->as($this->viewer())->get('/admin/welfare')->getContent(), ['Zubaida', 'Khatun']);
    }

    public function test_the_figures_are_kept_a_short_while_and_refresh_works_them_out_again(): void
    {
        $viewer = $this->viewer();
        $fund = $this->fund();
        $svc = app(FundService::class);
        $svc->recordDonation($fund, '1000', null, $this->actor([]));
        $this->as($viewer)->get('/admin/welfare')->assertOk()->assertSee('৳ 1,000');
        $svc->recordDonation($fund, '500', null, $this->actor([]));

        $this->as($viewer)->get('/admin/welfare')->assertOk()->assertSee('৳ 1,000')->assertDontSee('৳ 1,500');
        $this->as($viewer)->get('/admin/welfare?fresh=1')->assertOk()->assertSee('৳ 1,500');
    }

    public function test_a_bad_period_falls_back_and_a_custom_one_is_honoured(): void
    {
        $fund = $this->fund();
        app(FundService::class)->recordDonation($fund, '800', null, $this->actor([]), null, null, now()->subDays(60));
        $viewer = $this->viewer();

        $this->as($viewer)->get('/admin/welfare?period=banana')->assertOk()->assertSee('Last 30 days');
        $from = now()->subDays(70)->toDateString();
        $to = now()->subDays(50)->toDateString();
        $html = $this->as($viewer)->get("/admin/welfare?period=custom&from=$from&to=$to")->assertOk()->getContent();
        $this->assertStringContainsString('৳ 800', $html);
        $this->assertTileEqualsList($html, 'Received', $viewer, '800.00', 1);
    }
}
