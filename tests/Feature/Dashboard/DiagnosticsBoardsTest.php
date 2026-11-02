<?php

namespace Tests\Feature\Dashboard;

use App\Services\Blood\BloodOverview;
use App\Services\Lab\LabOverview;
use App\Services\Pharmacy\PharmacyOverview;
use App\Services\Radiology\RadiologyOverview;
use App\Support\Dashboard\DashboardPeriod;
use App\Support\Dashboard\Spread;
use Carbon\CarbonImmutable;

/** The laboratory, radiology, pharmacy and blood bank boards on the dashboard standard. Now is Thursday 12 Nov 2026, 10:00 in Dhaka (04:00 UTC). */
class DiagnosticsBoardsTest extends FrontDoorTestCase
{
    private function period(string $key = '7d'): DashboardPeriod
    {
        return DashboardPeriod::make($key, now: CarbonImmutable::now());
    }

    public function test_each_board_states_itself_and_refuses_a_person_without_the_right(): void
    {
        $this->assertStringContainsString('critical', $this->assertDashboardStatesItself('/admin/lab', $this->actor(['lab.dashboard.view', 'lab.order.view'])));
        $this->assertStringContainsString('scan', $this->assertDashboardStatesItself('/admin/radiology', $this->actor(['radiology.dashboard.view', 'radiology.order.view'])));
        $this->assertStringContainsString('prescriptions', $this->assertDashboardStatesItself('/admin/pharmacy', $this->actor(['pharmacy.dashboard.view'])));
        $this->assertStringContainsString('blood', $this->assertDashboardStatesItself('/admin/blood-bank', $this->actor(['bloodbank.dashboard.view'])));
        $this->assertDashboardRefuses('/admin/pharmacy', $this->actor(['pharmacy.stock.view']));
        $this->assertDashboardRefuses('/admin/blood-bank', $this->actor(['bloodbank.request.view']));
    }

    public function test_a_tile_links_only_where_the_person_may_go(): void
    {
        $with = $this->as($this->actor(['pharmacy.dashboard.view', 'pharmacy.safety_alert.view']))->get('/admin/pharmacy')->assertOk()->getContent();
        $this->assertNotNull($this->tileLink($with, 'Safety alerts open'));
        $this->assertNull($this->tileLink($with, 'Medicines out of stock'), 'no stock right, no link');
    }

    public function test_the_spread_gives_a_median_and_a_ninetieth_percentile_and_says_nothing_for_no_data(): void
    {
        $this->assertNull(Spread::of(collect()));
        $s = Spread::of(collect(range(1, 10))->map(fn ($n) => $n * 10));
        $this->assertSame([50, 90, 10], [$s['median'], $s['p90'], $s['n']]);
        $this->assertSame(90, Spread::minutesBetween('2026-11-12 04:00:00', '2026-11-12 05:30:00'));
    }

    public function test_pharmacy_counts_waiting_orders_dispensings_and_the_wait_in_dhaka_days(): void
    {
        $this->filled('pharmacy_orders', ['status' => 'pending', 'ordered_at' => '2026-11-12 03:00:00']);
        $this->filled('pharmacy_orders', ['status' => 'under_review', 'ordered_at' => '2026-11-12 03:00:00']);
        $done = $this->filled('pharmacy_orders', ['status' => 'dispensed', 'ordered_at' => '2026-11-12 02:00:00']);
        $this->filled('pharmacy_dispensings', ['order_id' => $done, 'dispensed_at' => '2026-11-12 02:30:00']);
        $yesterdayLate = $this->filled('pharmacy_orders', ['status' => 'dispensed', 'ordered_at' => '2026-11-11 17:00:00']);
        $this->filled('pharmacy_dispensings', ['order_id' => $yesterdayLate, 'dispensed_at' => '2026-11-11 17:10:00']);   // 23:10 Dhaka, 11 Nov: not today

        $s = app(PharmacyOverview::class)->summary($this->organization->id, $this->branch->id, $this->period());

        $this->assertSame([1, 1, 1], [$s['orders_pending'], $s['orders_under_review'], $s['dispensed_today']]);
        $this->assertSame(2, $s['current']['dispensed']);
        $this->assertSame([10, 30, 2], [$s['current']['wait']['median'], $s['current']['wait']['p90'], $s['current']['wait']['n']], 'ten and thirty minutes, nearest rank');
    }

    public function test_pharmacy_does_not_count_another_hospital(): void
    {
        $other = $this->otherHospital();
        $this->filled('pharmacy_orders', ['organization_id' => $other->id, 'branch_id' => null, 'status' => 'pending', 'ordered_at' => '2026-11-12 03:00:00']);

        $this->assertSame(0, app(PharmacyOverview::class)->summary($this->organization->id, $this->branch->id, $this->period())['orders_pending']);
    }

    public function test_radiology_counts_the_unreported_and_how_long_the_oldest_has_waited(): void
    {
        $item = fn (string $priority) => $this->filled('radiology_order_items', ['status' => 'completed', 'priority' => $priority]);
        $a = $this->filled('radiology_examinations', ['order_item_id' => $item('routine'), 'status' => 'completed', 'completed_at' => '2026-11-12 02:00:00']);
        $this->filled('radiology_examinations', ['order_item_id' => $item('stat'), 'status' => 'images_available', 'completed_at' => '2026-11-12 03:00:00']);
        $this->filled('radiology_examinations', ['order_item_id' => $item('routine'), 'status' => 'checked_in']);
        $this->filled('radiology_examinations', ['order_item_id' => $item('routine'), 'status' => 'in_progress']);

        $s = app(RadiologyOverview::class)->summary($this->organization->id, $this->branch->id, $this->period());

        $this->assertSame([1, 1, 2, 1, 120], [$s['waiting_patients'], $s['in_progress'], $s['awaiting_reporting'], $s['urgent_waiting'], $s['oldest_waiting_minutes']]);
        $this->assertNotNull($a);
    }

    public function test_lab_stages_add_up_and_a_reported_order_is_timed_to_its_last_approval(): void
    {
        $this->filled('lab_orders', ['status' => 'awaiting_collection', 'ordered_at' => '2026-11-12 03:00:00']);
        $this->filled('lab_orders', ['status' => 'awaiting_validation', 'ordered_at' => '2026-11-12 03:00:00']);
        $this->filled('lab_orders', ['status' => 'cancelled', 'ordered_at' => '2026-11-12 03:00:00', 'cancelled_at' => '2026-11-12 03:05:00']);

        $s = app(LabOverview::class)->summary($this->organization->id, $this->branch->id, $this->period());

        $this->assertSame([2, 1], [$s['to_work'], $s['awaiting_validation']]);
        $this->assertSame(array_sum(array_column($s['stages'], 'n')), $s['to_work'], 'the stages add up to the whole');
        $this->assertSame(2, $s['current']['orders'], 'a cancelled order is not counted');
        $this->assertNull($s['current']['tat'], 'no reported orders: no turnaround is claimed');
    }

    public function test_blood_shows_stock_against_the_minimum_and_names_nobody(): void
    {
        $s = app(BloodOverview::class)->summary($this->organization->id, $this->branch->id, $this->period());

        $this->assertNotEmpty($s['stock']);
        $this->assertSame(0, array_sum(array_column($s['stock'], 'available')));
        $this->assertGreaterThanOrEqual(0, $s['below_minimum']);
        $this->assertNull($s['current']['wait'], 'nothing issued: no wait is claimed');
        $this->assertStringNotContainsString('patient', strtolower(json_encode($s)));
    }
}
