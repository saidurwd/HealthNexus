<?php

namespace Tests\Feature\Dashboard;

use App\Models\Patient;
use App\Models\User;
use App\Services\Appointments\QueueService;
use App\Services\Dashboard\QueueBoardOverview;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;

/** The reception queue board: waits and busy doctors for today, token numbers only. */
class QueueBoardTest extends FrontDoorTestCase
{
    private function booking(int $providerId, string $status, string $token, ?string $generated, ?string $called = null, string $tokenStatus = 'waiting', ?int $org = null, ?Patient $patient = null): int
    {
        $a = $this->row('appointments', ['organization_id' => $org ?? $this->organization->id, 'patient_id' => $patient?->id ?? 1, 'doctor_id' => 1, 'provider_id' => $providerId, 'appointment_no' => 'AP-'.$token, 'appointment_date' => '2026-11-12', 'appointment_time' => '10:00', 'status' => $status, 'type' => 'scheduled', 'source' => 'walkin']);
        $this->row('appointment_tokens', ['organization_id' => $org ?? $this->organization->id, 'appointment_id' => $a, 'token_number' => $token, 'status' => $tokenStatus, 'generated_at' => $generated, 'called_at' => $called]);

        return $a;
    }

    private function receptionist(): User
    {
        return $this->actor(['appointments.queue']);
    }

    public function test_it_states_itself_reloads_and_is_refused_without_the_right(): void
    {
        $html = $this->assertDashboardStatesItself('/admin/queue-board', $this->receptionist());
        $this->assertStringContainsString('How long are people waiting', $html);
        $this->assertStringContainsString('Reloads every 60 seconds', $html);
        $this->assertStringContainsString('No one is booked or waiting today.', $html, 'an empty day says so');
        $this->assertDashboardRefuses('/admin/queue-board', $this->actor(['encounters.view']));
    }

    public function test_waits_are_worked_out_from_when_the_token_was_made(): void
    {
        $dr = $this->row('providers', ['name' => 'Dr Rahim', 'provider_type' => 'doctor']);
        $this->booking($dr, 'checked_in', 'T-001', '2026-11-12 03:35:00');                                   // 09:35 Dhaka → 25 min
        $this->booking($dr, 'checked_in', 'T-002', '2026-11-12 03:55:00');                                   // 5 min
        $this->booking($dr, 'in_progress', 'T-003', '2026-11-12 03:00:00', '2026-11-12 03:10:00', 'called');  // waited 10 min, now with the doctor
        $this->booking($dr, 'completed', 'T-004', '2026-11-12 02:00:00', '2026-11-12 02:20:00', 'completed'); // waited 20 min, done
        $this->booking($dr, 'no_show', 'T-005', null, null, 'cancelled');

        $s = app(QueueBoardOverview::class)->summary($this->organization->id, $this->branch->id);

        $this->assertSame([2, 25, 2, 1, 1], [$s['waiting'], $s['longest'], $s['served_count'], $s['seen'], $s['no_shows']]);
        $this->assertSame(15.0, $s['average_served'], 'the two who were called waited 10 and 20 minutes: their average');
        $row = $s['by_doctor'][0];
        $this->assertSame(['Dr Rahim', 2, 1, 1, 25, 'T-001'], [$row['doctor'], $row['waiting'], $row['in_consultation'], $row['done'], $row['longest'], $row['next']]);
    }

    public function test_the_page_shows_token_numbers_and_never_a_name(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id, 'first_name' => 'Nasima', 'last_name' => 'Begum']);
        $dr = $this->row('providers', ['name' => 'Dr Karim', 'provider_type' => 'doctor']);
        $this->booking($dr, 'checked_in', 'T-777', '2026-11-12 03:30:00', null, 'waiting', null, $patient);

        $html = $this->as($this->receptionist())->get('/admin/queue-board')->assertOk()->getContent();

        $this->assertStringContainsString('T-777', $html);
        $this->assertStringContainsString('Dr Karim', $html);
        $this->assertNoPatientIdentity($html, ['Nasima', 'Begum', $patient->enterprise_patient_no]);
    }

    public function test_the_waiting_figure_opens_the_queue_and_matches_it(): void
    {
        $dr = $this->row('providers', ['name' => 'Dr Rahim', 'provider_type' => 'doctor']);
        $this->booking($dr, 'checked_in', 'T-001', '2026-11-12 03:30:00');
        $this->booking($dr, 'checked_in', 'T-002', '2026-11-12 03:40:00');
        $user = $this->actor(['appointments.queue', 'appointments.view']);
        $html = $this->as($user)->get('/admin/queue-board')->assertOk()->getContent();

        $this->assertSame(route('admin.queue.index'), $this->tileLink($html, 'Waiting now'));
        $queue = app(QueueService::class)->todaysQueue($this->organization->id, $this->branch->id);
        $this->assertCount(2, $queue->filter(fn ($a) => $a->token?->status === 'waiting'), 'the queue page lists the same two');
    }

    public function test_another_hospitals_queue_is_not_counted(): void
    {
        $other = $this->otherHospital();
        $dr = $this->row('providers', ['name' => 'Dr Other', 'provider_type' => 'doctor', 'organization_id' => $other->id]);
        $this->booking($dr, 'checked_in', 'T-999', '2026-11-12 03:30:00', null, 'waiting', $other->id);

        $s = app(QueueBoardOverview::class)->summary($this->organization->id, $this->branch->id);
        $this->assertSame(0, $s['waiting']);
        $this->assertSame([], $s['by_doctor']);
    }

    public function test_the_hospital_can_set_a_target_for_the_longest_wait(): void
    {
        $dr = $this->row('providers', ['name' => 'Dr Rahim', 'provider_type' => 'doctor']);
        $this->booking($dr, 'checked_in', 'T-001', '2026-11-12 03:00:00'); // 60 minutes
        $user = $this->receptionist();
        $this->assertStringNotContainsString('Off target', $this->as($user)->get('/admin/queue-board')->getContent(), 'no target ships');

        app(SettingsService::class)->set('dashboard.target.opd_longest_wait', '30');
        Cache::flush();
        $this->assertStringContainsString('Off target', $this->as($user)->get('/admin/queue-board')->getContent());
    }
}
