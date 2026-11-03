<?php

namespace Tests\Feature\Dashboard;

use App\Models\Patient;
use App\Models\User;
use App\Services\Dashboard\TodayOverview;
use App\Services\SettingsService;
use App\Support\Dashboard\Days;
use Carbon\CarbonImmutable;

/** "Today in the hospital": the director's figures, live, in Dhaka days, each against the same weekday of the last four weeks. Now is Thursday 12 Nov 2026, 10:00 in Dhaka. */
class TodayTest extends FrontDoorTestCase
{
    private function summary(): array
    {
        return app(TodayOverview::class)->summary($this->organization->id);
    }

    private function opd(string $date, int $n = 1, string $status = 'completed'): void
    {
        for ($i = 0; $i < $n; $i++) {
            $this->row('encounters', ['encounter_type' => 'OPD', 'encounter_date' => $date, 'status' => $status, 'encounter_no' => 'E-'.uniqid(), 'patient_id' => 1]);
        }
    }

    private function director(array $extra = []): User
    {
        return $this->actor(array_merge(['dashboard.today.view'], $extra));
    }

    public function test_it_states_itself_is_refused_without_the_right_and_says_when_and_how_it_reloads(): void
    {
        $html = $this->assertDashboardStatesItself('/today', $this->director());
        $this->assertStringContainsString('Is today going well?', $html);
        $this->assertStringContainsString('Reloads every 300 seconds', $html);
        $this->assertStringContainsString('Thursday', $html);
        $this->assertDashboardRefuses('/today', $this->actor(['encounters.view']));
    }

    public function test_outpatients_today_are_compared_with_the_average_of_the_same_weekday_over_four_weeks(): void
    {
        $this->opd('2026-11-12', 6);                                         // today, Thursday
        foreach ([['2026-11-05', 4], ['2026-10-29', 8], ['2026-10-22', 2], ['2026-10-15', 6]] as [$d, $n]) {
            $this->opd($d, $n);                                              // the four Thursdays before: average 5
        }
        $this->opd('2026-11-11', 20);                                        // a Wednesday must not count as "the usual Thursday"
        $this->opd('2026-11-12', 3, 'cancelled');                            // cancelled visits are not seen

        $s = $this->summary();
        $this->assertSame(6, $s['opd']['today']);
        $this->assertSame(5.0, $s['opd']['usual']);
        $html = $this->as($this->director())->get('/today')->assertOk()->getContent();
        $this->assertStringContainsString('+20%', $html);
        $this->assertStringContainsString('the usual Thursday', $html);
    }

    public function test_a_day_in_dhaka_starts_at_six_in_the_evening_utc(): void
    {
        // 12 Nov 01:00 Dhaka is 11 Nov 19:00 UTC: today. 11 Nov 23:00 Dhaka is 11 Nov 17:00 UTC: yesterday.
        $this->row('ed_visits', ['visit_number' => 'V1', 'patient_id' => 1, 'arrival_at' => '2026-11-11 19:00:00', 'status' => 'dispositioned']);
        $this->row('ed_visits', ['visit_number' => 'V2', 'patient_id' => 1, 'arrival_at' => '2026-11-11 17:00:00', 'status' => 'dispositioned']);
        $this->row('ed_visits', ['visit_number' => 'V3', 'patient_id' => 1, 'arrival_at' => '2026-11-12 03:00:00', 'status' => 'in_treatment']);

        $s = $this->summary();
        $this->assertSame(2, $s['ed']['today'], 'the two that arrived after Dhaka midnight');
        $this->assertSame(1, $s['ed']['in_ed_now'], 'only the one still being treated');
        $trend = $s['ed']['trend'];
        $this->assertCount(14, $trend);
        $this->assertSame(2.0, (float) end($trend));
        $this->assertSame(1.0, (float) $trend[12], 'yesterday has the late-evening arrival');
    }

    public function test_beds_inpatients_and_the_days_movements(): void
    {
        foreach (range(1, 4) as $i) {
            $this->row('ipd_beds', ['room_id' => 1, 'bed_code' => "B$i", 'is_active' => true, 'status' => 'available']);
        }
        $this->row('ipd_beds', ['room_id' => 1, 'bed_code' => 'OFF', 'is_active' => false, 'status' => 'available']);
        $this->row('ipd_bed_allocations', ['admission_id' => 1, 'patient_id' => 1, 'bed_id' => 1, 'allocated_at' => now(), 'status' => 'active']);
        $this->row('ipd_bed_allocations', ['admission_id' => 2, 'patient_id' => 1, 'bed_id' => 2, 'allocated_at' => now()->subDay(), 'released_at' => now(), 'status' => 'released']);
        $this->row('ipd_admissions', ['admission_number' => 'A1', 'patient_id' => 1, 'encounter_id' => 1, 'admitted_at' => '2026-11-12 02:00:00', 'status' => 'admitted']);
        $this->row('ipd_admissions', ['admission_number' => 'A2', 'patient_id' => 1, 'encounter_id' => 1, 'admitted_at' => '2026-11-09 02:00:00', 'status' => 'discharged', 'actual_discharge_date' => '2026-11-12 03:30:00']);

        $i = $this->summary()['ipd'];
        $this->assertSame([4, 1, 25.0], [$i['beds'], $i['occupied'], $i['occupancy']], 'the inactive bed is not a bed, the released allocation is not an occupant');
        $this->assertSame([1, 1, 1], [$i['inpatients'], $i['admitted_today'], $i['discharged_today']]);
    }

    public function test_theatre_money_and_alerts(): void
    {
        $this->row('ot_surgeries', ['surgery_number' => 'S1', 'patient_id' => 1, 'encounter_id' => 1, 'scheduled_date' => '2026-11-12', 'status' => 'scheduled']);
        $this->row('ot_surgeries', ['surgery_number' => 'S2', 'patient_id' => 1, 'encounter_id' => 1, 'scheduled_date' => '2026-11-12', 'status' => 'approved']);
        $this->row('ot_surgeries', ['surgery_number' => 'S3', 'patient_id' => 1, 'encounter_id' => 1, 'scheduled_date' => '2026-11-12', 'status' => 'postponed']);
        $this->row('ot_surgeries', ['surgery_number' => 'S4', 'patient_id' => 1, 'encounter_id' => 1, 'scheduled_date' => '2026-11-11', 'status' => 'scheduled']);
        foreach ([['2026-11-12', 'finalized', 1000], ['2026-11-12', 'paid', 500], ['2026-11-12', 'cancelled', 9999], ['2026-11-12', 'draft', 9999], ['2026-11-05', 'paid', 700]] as $i => [$d, $st, $amt]) {
            $this->row('billing_invoices', ['invoice_number' => "I$i", 'patient_id' => 1, 'invoice_date' => $d, 'status' => $st, 'grand_total' => $amt, 'finalized_at' => now(), 'invoice_type' => 'opd']);
        }
        $this->row('billing_payments', ['payment_number' => 'P1', 'patient_id' => 1, 'invoice_id' => 1, 'payment_method_id' => 1, 'received_by' => 1, 'payment_date' => '2026-11-12', 'amount' => 800, 'status' => 'completed']);
        $this->row('billing_payments', ['payment_number' => 'P2', 'patient_id' => 1, 'invoice_id' => 1, 'payment_method_id' => 1, 'received_by' => 1, 'payment_date' => '2026-11-05', 'amount' => 400, 'status' => 'completed']);
        $this->row('clinical_alerts', ['category' => 'clinical', 'severity' => 'critical', 'message' => 'm', 'source_module' => 'lab', 'raised_at' => now(), 'status' => 'open', 'ack_required' => true]);
        $this->row('clinical_alerts', ['category' => 'clinical', 'severity' => 'warning', 'message' => 'm', 'source_module' => 'lab', 'raised_at' => now(), 'status' => 'open', 'ack_required' => true]);
        $this->row('clinical_alerts', ['category' => 'clinical', 'severity' => 'info', 'message' => 'm', 'source_module' => 'lab', 'raised_at' => now(), 'status' => 'open', 'ack_required' => false]);

        $s = $this->summary();
        $this->assertSame([2, 1], [$s['theatre']['cases_today'], $s['theatre']['called_off_today']]);
        $this->assertSame(1500.0, $s['billed']['today'], 'finalized and paid today, not cancelled or draft');
        $this->assertSame(175.0, $s['billed']['usual'], 'one Thursday of the four had 700: 700 / 4');
        $this->assertSame(800.0, $s['collected']['today']);
        $this->assertSame(100.0, $s['collected']['usual']);
        $this->assertSame([2, 1], [$s['alerts_open'], $s['critical_open']], 'only alerts that need acknowledging');
    }

    public function test_the_trend_has_fourteen_days_ending_today_with_empty_days_as_zero(): void
    {
        $this->opd('2026-11-12', 2);
        $this->opd('2026-11-10', 5);
        $t = $this->summary()['opd']['trend'];
        $this->assertCount(14, $t);
        $this->assertSame([2.0, 0.0, 5.0], [(float) $t[13], (float) $t[12], (float) $t[11]]);
    }

    public function test_nothing_from_another_hospital_is_counted_and_no_patient_is_named(): void
    {
        $other = $this->otherHospital();
        $this->row('encounters', ['organization_id' => $other->id, 'encounter_type' => 'OPD', 'encounter_date' => '2026-11-12', 'status' => 'completed', 'encounter_no' => 'Z', 'patient_id' => 1]);
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id, 'first_name' => 'Rubina', 'last_name' => 'Parveen']);
        $this->row('encounters', ['encounter_type' => 'OPD', 'encounter_date' => '2026-11-12', 'status' => 'completed', 'encounter_no' => 'E1', 'patient_id' => $patient->id]);

        $html = $this->as($this->director())->get('/today')->assertOk()->getContent();
        $this->assertSame(1, $this->summary()['opd']['today']);
        $this->assertNoPatientIdentity($html, ['Rubina', 'Parveen']);
    }

    public function test_a_figure_links_only_to_a_page_the_person_may_open(): void
    {
        $this->row('ipd_beds', ['room_id' => 1, 'bed_code' => 'B1', 'is_active' => true, 'status' => 'available']);
        $plain = $this->as($this->director())->get('/today')->getContent();
        $this->assertNull($this->tileLink($plain, 'Inpatients now'));
        $with = $this->as($this->director(['ipd.dashboard.view']))->get('/today')->getContent();
        $this->assertSame(route('admin.ipd.dashboard'), $this->tileLink($with, 'Inpatients now'));
    }

    public function test_the_figures_follow_the_hospitals_digit_and_grouping_choice(): void
    {
        $this->row('billing_invoices', ['invoice_number' => 'I1', 'patient_id' => 1, 'invoice_date' => '2026-11-12', 'status' => 'paid', 'grand_total' => 1234567, 'finalized_at' => now(), 'invoice_type' => 'opd']);
        app(SettingsService::class)->set('ux.number_grouping', 'south_asian');
        $this->assertStringContainsString('৳ 12,34,567', $this->as($this->director())->get('/today')->getContent());
    }

    public function test_the_day_helper_buckets_a_utc_time_by_its_dhaka_date(): void
    {
        $this->assertSame(['2026-11-11 18:00:00', '2026-11-12 17:59:59'], array_map(fn ($d) => $d->format('Y-m-d H:i:s'), Days::bounds(CarbonImmutable::parse('2026-11-12 10:00:00', 'Asia/Dhaka'))));
    }
}
