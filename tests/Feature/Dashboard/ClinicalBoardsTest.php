<?php

namespace Tests\Feature\Dashboard;

use App\Models\Patient;
use App\Services\Icu\IcuOverview;
use App\Services\Ipd\IpdOverview;
use App\Services\Nursing\NursingOverview;
use App\Services\Ot\OtOverview;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;

/** The IPD, Nursing, ICU and OT boards on the dashboard standard. Now is Thursday 12 Nov 2026, 10:00 in Dhaka (04:00 UTC). */
class ClinicalBoardsTest extends FrontDoorTestCase
{
    private function board(string $url, array $permissions): string
    {
        return $this->assertDashboardStatesItself($url, $this->actor($permissions));
    }

    // ---- IPD ----------------------------------------------------------------------------------------------------------------------------------

    private function ward(string $name, array $bedStatuses): int
    {
        $ward = $this->row('ipd_wards', ['code' => strtoupper(substr($name, 0, 3)), 'name' => $name]);
        $room = $this->row('ipd_rooms', ['ward_id' => $ward, 'room_number' => $name.'-1']);
        foreach ($bedStatuses as $i => $status) {
            $this->row('ipd_beds', ['room_id' => $room, 'bed_code' => $name.'-'.$i, 'status' => $status, 'is_active' => true]);
        }

        return $ward;
    }

    private function admission(string $admitted, string $status = 'active', array $extra = []): int
    {
        return $this->row('ipd_admissions', $extra + ['admission_number' => 'A-'.uniqid(), 'patient_id' => 1, 'encounter_id' => 1, 'admitted_at' => $admitted, 'status' => $status]);
    }

    public function test_ipd_states_itself_refuses_without_the_right_and_has_honest_empty_states(): void
    {
        $html = $this->board('/admin/ipd', ['ipd.dashboard.view']);
        $this->assertStringContainsString('Where are the beds', $html);
        $this->assertStringContainsString('No wards or beds are set up.', $html);
        $this->assertDashboardRefuses('/admin/ipd', $this->actor(['ipd.bed.view']));
    }

    public function test_ipd_occupancy_is_of_the_usable_beds_and_each_ward_is_shown(): void
    {
        $this->ward('Male', ['occupied', 'occupied', 'available', 'out_of_service']);
        $this->ward('Female', ['occupied', 'available', 'maintenance', 'cleaning']);

        $s = app(IpdOverview::class)->summary($this->organization->id, $this->branch->id);

        $this->assertSame([8, 6, 3, 50.0], [$s['total_beds'], $s['operational_beds'], $s['occupied_beds'], $s['occupancy_rate']], 'out of service and maintenance beds are not usable beds');
        $this->assertSame(['Female', 'Male'], array_column($s['by_ward'], 'ward'));
        $this->assertSame([[3, 33.3], [3, 66.7]], [[$s['by_ward'][0]['beds'], $s['by_ward'][0]['rate']], [$s['by_ward'][1]['beds'], $s['by_ward'][1]['rate']]], 'a ward\'s occupancy is of its usable beds, like the hospital\'s');
        $this->assertSame([1, 1, 1], [$s['available_beds'] - 1, $s['cleaning_beds'], $s['maintenance_beds']]);
    }

    public function test_ipd_admissions_discharges_and_delays_use_dhaka_days_and_the_same_weekday(): void
    {
        $this->admission('2026-11-11 19:00:00');                                                   // 01:00 Dhaka, 12 Nov: today
        $this->admission('2026-11-11 17:00:00', 'admitted');                                      // 23:00 Dhaka, 11 Nov: yesterday
        $this->admission('2026-11-05 04:00:00');                                                   // a Thursday before
        $this->admission('2026-10-29 04:00:00');
        $this->admission('2026-10-20 04:00:00', 'discharged', ['actual_discharge_date' => '2026-11-12 03:00:00']);
        $this->admission('2026-11-01 04:00:00', 'active', ['expected_discharge_date' => '2026-11-12']);
        $this->admission('2026-11-01 04:00:00', 'active', ['expected_discharge_date' => '2026-11-10']);
        $this->admission('2026-11-01 04:00:00', 'discharged', ['expected_discharge_date' => '2026-11-09']);

        $s = app(IpdOverview::class)->summary($this->organization->id, $this->branch->id);

        $this->assertSame(1, $s['admissions_today']);
        $this->assertSame(0.5, $s['admissions_usual'], 'one admission on each of two of the last four Thursdays: 2 / 4');
        $this->assertSame(1, $s['discharges_today']);
        $this->assertSame(1, $s['expected_discharges_today']);
        $this->assertSame(1, $s['delayed_discharges'], 'still active, expected before today');
        $this->assertSame(14, count($s['flow']));
        $this->assertSame(1, $s['flow'][13]['admitted']);
    }

    public function test_ipd_waiting_counts_and_another_hospitals_beds_are_not_mixed_in(): void
    {
        $this->row('ipd_bed_movements', ['admission_id' => 1, 'patient_id' => 1, 'movement_type' => 'transfer', 'status' => 'requested', 'moved_at' => now()]);
        $other = $this->otherHospital();
        $this->row('ipd_wards', ['organization_id' => $other->id, 'code' => 'ZZZ', 'name' => 'Foreign']);

        $s = app(IpdOverview::class)->summary($this->organization->id, $this->branch->id);

        $this->assertSame(1, $s['pending_transfers']);
        $this->assertSame([], $s['by_ward'], 'another hospital\'s wards never show');
    }

    public function test_ipd_figures_link_only_to_pages_the_person_may_open_and_carry_no_patient(): void
    {
        $this->ward('Male', ['occupied']);
        $plain = $this->board('/admin/ipd', ['ipd.dashboard.view']);
        $this->assertNull($this->tileLink($plain, 'Inpatients now'));
        $with = $this->board('/admin/ipd', ['ipd.dashboard.view', 'ipd.admission.view', 'ipd.bed.view', 'ipd.discharge.view']);
        $this->assertSame(route('admin.ipd.admissions.index'), $this->tileLink($with, 'Inpatients now'));
        $this->assertSame(route('admin.ipd.bed-board.index'), $this->tileLink($with, 'Bed occupancy'));
        $this->assertSame(route('admin.ipd.discharge.index'), $this->tileLink($with, 'Delayed discharges'));
    }

    // ---- Nursing ------------------------------------------------------------------------------------------------------------------------------

    private function task(string $status, string $due, ?int $nurse = null): void
    {
        $this->row('nursing_tasks', ['status' => $status, 'due_at' => $due, 'task_type' => 'obs', 'episode_id' => 1, 'admission_id' => 1, 'patient_id' => 1, 'assigned_nurse_id' => $nurse]);
    }

    public function test_nursing_overdue_means_still_to_do_and_past_its_time_whatever_the_status_says(): void
    {
        $me = $this->actor(['nursing.dashboard.view']);
        $this->task('pending', '2026-11-12 02:00:00', $me->id);   // past, mine
        $this->task('overdue', '2026-11-12 02:30:00', null);      // past, marked overdue
        $this->task('pending', '2026-11-12 09:00:00');            // not yet due
        $this->task('completed', '2026-11-12 01:00:00');          // done
        $this->row('nursing_medication_administrations', ['episode_id' => 1, 'admission_id' => 1, 'encounter_id' => 1, 'patient_id' => 1, 'scheduled_at' => '2026-11-12 02:00:00', 'status' => 'due']);
        $this->row('nursing_medication_administrations', ['episode_id' => 1, 'admission_id' => 1, 'encounter_id' => 1, 'patient_id' => 1, 'scheduled_at' => '2026-11-12 08:00:00', 'status' => 'scheduled']);
        $this->row('nursing_medication_administrations', ['episode_id' => 1, 'admission_id' => 1, 'encounter_id' => 1, 'patient_id' => 1, 'scheduled_at' => '2026-11-12 01:00:00', 'status' => 'administered']);

        $m = app(NursingOverview::class)->summary($this->organization->id, $this->branch->id, $me->id);

        $this->assertSame([2, 1, 1], [$m['overdue_tasks'], $m['my_overdue_tasks'], $m['pending_tasks'] - 1], 'a pending task past its time is overdue, a pending one not yet due is not');
        $this->assertSame([1, 2], [$m['overdue_medications'], $m['due_medications']]);
        $this->assertSame(14, count($m['tasks']));
    }

    public function test_nursing_own_counts_are_the_nurses_own_and_the_page_reloads_itself(): void
    {
        $a = $this->actor(['nursing.dashboard.view']);
        $b = $this->actor(['nursing.dashboard.view']);
        $this->task('pending', '2026-11-12 02:00:00', $a->id);
        $this->task('pending', '2026-11-12 02:00:00', $a->id);
        $this->task('pending', '2026-11-12 02:00:00', $b->id);

        $this->assertSame(2, app(NursingOverview::class)->summary($this->organization->id, $this->branch->id, $a->id)['my_overdue_tasks']);
        $this->assertSame(1, app(NursingOverview::class)->summary($this->organization->id, $this->branch->id, $b->id)['my_overdue_tasks']);
        $this->as($a)->get('/admin/nursing')->assertOk()->assertSee('Reloads every 60 seconds');
        $this->as($b)->get('/admin/nursing')->assertOk();
        $this->assertSame(1, $this->as($b)->get('/admin/nursing')->viewData('metrics')['my_overdue_tasks'], 'the second nurse is not shown the first nurse\'s count from the cache');
    }

    public function test_nursing_alerts_handovers_and_scope(): void
    {
        $this->row('nursing_alerts', ['episode_id' => 1, 'admission_id' => 1, 'patient_id' => 1, 'trigger_description' => 'x', 'severity' => 'high']);
        $this->row('nursing_alerts', ['episode_id' => 1, 'admission_id' => 1, 'patient_id' => 1, 'trigger_description' => 'x', 'severity' => 'critical', 'resolved_at' => now()]);
        $this->row('nursing_handover', ['episode_id' => 1, 'admission_id' => 1, 'outgoing_nurse_id' => 1, 'status' => 'pending_acknowledgement']);
        $this->row('nursing_episodes', ['admission_id' => 1, 'patient_id' => 1, 'encounter_id' => 1, 'start_at' => now(), 'status' => 'active']);
        $other = $this->otherHospital();
        $this->row('nursing_alerts', ['organization_id' => $other->id, 'episode_id' => 1, 'admission_id' => 1, 'patient_id' => 1, 'trigger_description' => 'x', 'severity' => 'high']);

        $m = app(NursingOverview::class)->summary($this->organization->id, $this->branch->id, 1);

        $this->assertSame([1, 1, 1], [$m['critical_alerts'], $m['pending_handover'], $m['ward_patients']], 'resolved and other-hospital alerts are not counted');
    }

    // ---- ICU ----------------------------------------------------------------------------------------------------------------------------------

    public function test_icu_alerts_beds_and_the_dhaka_day(): void
    {
        $this->row('ipd_beds', ['room_id' => 1, 'bed_code' => 'I1', 'status' => 'occupied', 'is_active' => true, 'icu_capable' => true]);
        $this->row('ipd_beds', ['room_id' => 1, 'bed_code' => 'I2', 'status' => 'available', 'is_active' => true, 'icu_capable' => true]);
        $this->row('ipd_beds', ['room_id' => 1, 'bed_code' => 'W1', 'status' => 'available', 'is_active' => true, 'icu_capable' => false]);
        $this->row('icu_episodes', ['episode_number' => 'E1', 'patient_id' => 1, 'encounter_id' => 1, 'admitted_at' => '2026-11-11 19:00:00', 'status' => 'admitted']);
        $this->row('icu_episodes', ['episode_number' => 'E2', 'patient_id' => 1, 'encounter_id' => 1, 'admitted_at' => '2026-11-11 17:00:00', 'discharged_at' => '2026-11-12 02:00:00', 'status' => 'discharged']);
        $this->row('icu_critical_alerts', ['episode_id' => 1, 'rule' => 'spo2', 'triggered_at' => '2026-11-12 03:00:00']);                 // 60 minutes before now
        $this->row('icu_critical_alerts', ['episode_id' => 1, 'rule' => 'hr', 'triggered_at' => '2026-11-12 03:30:00']);
        $this->row('icu_critical_alerts', ['episode_id' => 1, 'rule' => 'bp', 'triggered_at' => '2026-11-12 02:00:00', 'acknowledged_at' => '2026-11-12 02:10:00']);
        $this->row('icu_admission_requests', ['patient_id' => 1, 'encounter_id' => 1, 'status' => 'requested']);

        $m = app(IcuOverview::class)->summary($this->organization->id, $this->branch->id);

        $this->assertSame([1, 50.0, 1, 1], [$m['current_patients'], $m['occupancy'], $m['beds_available'], $m['beds_occupied']], 'ward beds are not ICU beds');
        $this->assertSame([2, 60], [$m['unacknowledged_alerts'], $m['oldest_alert_minutes']], 'the oldest unanswered alert has waited 60 minutes (the acknowledged one does not count)');
        $this->assertSame([1, 1, 1], [$m['admissions_today'], $m['discharges_today'], $m['pending_requests']]);
        $this->assertSame(3.0, (float) array_sum(array_column($m['alerts'], 'raised')));
    }

    public function test_icu_page_states_itself_and_lists_the_alerts_that_wait_for_someone(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id, 'first_name' => 'Zubaida']);
        $ep = $this->row('icu_episodes', ['episode_number' => 'E1', 'patient_id' => $patient->id, 'encounter_id' => 1, 'admitted_at' => now(), 'status' => 'admitted']);
        $this->row('icu_critical_alerts', ['episode_id' => $ep, 'rule' => 'low oxygen', 'triggered_at' => now()->subMinutes(20)]);

        $html = $this->board('/admin/icu', ['icu.dashboard.view', 'icu.alert.view']);

        $this->assertStringContainsString('Who is critical', $html);
        $this->assertStringContainsString('low oxygen', $html);
        $this->assertStringContainsString('Zubaida', $html, 'the clinical team\'s own worklist names the patient');
        $this->assertSame(route('admin.icu.alerts.index'), $this->tileLink($html, 'Alerts not acknowledged'));
        $this->assertDashboardRefuses('/admin/icu', $this->actor(['icu.alert.view']));
    }

    // ---- OT -----------------------------------------------------------------------------------------------------------------------------------

    private function surgery(string $date, string $status, ?string $planned = null, ?string $actual = null): void
    {
        $this->row('ot_surgeries', ['surgery_number' => 'S-'.uniqid(), 'patient_id' => 1, 'encounter_id' => 1, 'scheduled_date' => $date, 'status' => $status, 'scheduled_start_at' => $planned, 'actual_start_at' => $actual]);
    }

    public function test_ot_how_late_cases_start_and_how_many_are_called_off(): void
    {
        $this->surgery('2026-11-12', 'scheduled', '2026-11-12 03:00:00');
        $this->surgery('2026-11-12', 'procedure_completed', '2026-11-12 02:00:00', '2026-11-12 02:30:00');       // 30 minutes late
        $this->surgery('2026-11-10', 'closed', '2026-11-10 02:00:00', '2026-11-10 03:00:00');                     // 60 minutes late
        $this->surgery('2026-11-10', 'closed', '2026-11-10 05:00:00', '2026-11-10 04:50:00');                     // early: counts as on time (0)
        $this->surgery('2026-11-12', 'cancelled');
        $this->surgery('2026-11-11', 'postponed');
        $this->surgery('2026-09-01', 'cancelled');                                                              // older than 30 days

        $m = app(OtOverview::class)->summary($this->organization->id, $this->branch->id);

        $this->assertSame([2, 1, 1], [$m['scheduled_today'], $m['completed_today'], $m['cancelled_today']], 'cases today are the ones that still stand; the called-off one has its own figure');
        $this->assertSame([30.0, 3], [$m['average_start_delay'], $m['delay_cases']], '(30 + 60 + 0) / 3');
        $this->assertSame([2, 6], [$m['called_off_30d'], $m['planned_30d']]);
        $this->assertSame(33.3, $m['called_off_rate']);
        $this->assertSame(14, count($m['per_day']));
    }

    public function test_ot_with_nothing_started_says_so_instead_of_showing_a_false_zero(): void
    {
        $m = app(OtOverview::class)->summary($this->organization->id, $this->branch->id);
        $this->assertNull($m['average_start_delay']);
        $this->assertNull($m['called_off_rate']);
        $html = $this->board('/admin/ot', ['ot.dashboard.view']);
        $this->assertStringContainsString('No started cases in 30 days.', $html);
        $this->assertStringContainsString('No cases are planned for today.', $html);
        $this->assertStringContainsString('—', $html, 'a figure that cannot be worked out is a dash, not 0');
    }

    public function test_ot_a_hospital_can_set_a_target_for_late_starts(): void
    {
        $this->surgery('2026-11-10', 'closed', '2026-11-10 02:00:00', '2026-11-10 03:00:00');
        $user = $this->actor(['ot.dashboard.view']);
        $this->assertStringNotContainsString('Off target', $this->as($user)->get('/admin/ot')->getContent(), 'no target ships');

        app(SettingsService::class)->set('dashboard.target.ot_start_delay', '15');
        Cache::flush();
        $this->assertStringContainsString('Off target', $this->as($user)->get('/admin/ot')->getContent());
    }

    public function test_every_clinical_board_is_scoped_cached_per_person_and_refreshes_on_request(): void
    {
        $user = $this->actor(['ipd.dashboard.view']);
        $this->ward('Male', ['occupied']);
        $this->as($user)->get('/admin/ipd')->assertOk()->assertViewHas('summary', fn ($s) => $s['total_beds'] === 1);
        $this->row('ipd_beds', ['room_id' => 1, 'bed_code' => 'X', 'status' => 'available', 'is_active' => true]);
        $this->as($user)->get('/admin/ipd')->assertOk()->assertViewHas('summary', fn ($s) => $s['total_beds'] === 1);
        $this->as($user)->get('/admin/ipd?fresh=1')->assertOk()->assertViewHas('summary', fn ($s) => $s['total_beds'] === 2);
    }
}
