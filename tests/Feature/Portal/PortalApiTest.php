<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\Portal\PortalAccessLog;
use App\Models\Portal\PortalResultRelease;
use App\Models\User;
use App\Services\Portal\PortalAuthService;
use App\Services\Portal\ResultReleaseService;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\Sanctum;

class PortalApiTest extends PortalTestCase
{
    public function test_the_whole_signup_works_over_the_api_and_the_token_only_carries_the_portal_ability(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $device = ['uid' => 'abc-1', 'name' => 'Rina phone', 'platform' => 'android'];

        $start = $this->postJson('/api/v1/portal/auth/register/start', ['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => '1990-04-12', 'phone' => '01712345678'])->assertOk()->json('data.challenge');
        $done = $this->postJson('/api/v1/portal/auth/register/complete', ['challenge' => $start, 'code' => $this->lastCode('8801712345678'), 'password' => 'Strong-Pass-1', 'accept_terms' => true, 'device' => $device])->assertCreated();

        $token = $done->json('data.token');
        $this->assertNotEmpty($token);
        $this->getJson('/api/v1/portal/me', ['Authorization' => "Bearer $token"])->assertOk()->assertJsonPath('data.people.0.relation', 'self')->assertJsonPath('data.people.0.patient_no', $patient->enterprise_patient_no);
    }

    public function test_anonymous_callers_and_staff_tokens_are_turned_away_from_patient_routes(): void
    {
        $this->getJson('/api/v1/portal/me')->assertUnauthorized();

        Sanctum::actingAs($staff = User::factory()->create(), ['*']);
        $this->getJson('/api/v1/portal/me')->assertUnauthorized();
    }

    public function test_a_patient_token_cannot_reach_a_staff_api_route(): void
    {
        $headers = $this->bearer($this->account());
        $this->getJson('/api/v1/auth/me', $headers)->assertForbidden();
        $this->getJson('/api/v1/tenant/context', $headers)->assertForbidden();
    }

    public function test_a_suspended_login_loses_its_token_at_once(): void
    {
        $account = $this->account();
        $headers = $this->bearer($account);
        $this->getJson('/api/v1/portal/me', $headers)->assertOk();

        app(PortalAuthService::class)->setStatus($account, 'suspended', 'test');
        app('auth')->forgetGuards();
        $this->getJson('/api/v1/portal/me', $headers)->assertUnauthorized();
    }

    public function test_a_get_sends_an_etag_and_a_repeat_gets_304(): void
    {
        $headers = $this->bearer($this->account());
        $first = $this->getJson('/api/v1/portal/home', $headers)->assertOk();
        $etag = $first->headers->get('ETag');
        $this->assertNotEmpty($etag);
        $this->assertSame('v1', $first->headers->get('X-Api-Version'));

        $this->getJson('/api/v1/portal/home', $headers + ['If-None-Match' => $etag])->assertStatus(304);
    }

    public function test_booking_uses_the_staff_slot_lock_so_the_same_slot_cannot_be_booked_twice(): void
    {
        $slot = $this->slot($this->provider());
        $token = Crypt::encryptString((string) $slot->id);
        $a = $this->bearer($this->account($this->patient()));
        $b = $this->bearer($this->account($this->patient()));

        $this->postJson('/api/v1/portal/appointments', ['slot' => $token, 'reason' => 'Fever'], $a)->assertCreated()->assertJsonPath('data.status', 'scheduled');
        $this->postJson('/api/v1/portal/appointments', ['slot' => $token], $b)->assertStatus(422);

        $this->assertSame(1, Appointment::query()->where('slot_id', $slot->id)->count());
        $this->assertSame('patient_portal', Appointment::query()->first()->source);
    }

    public function test_a_patient_sees_only_their_own_appointments_and_cannot_cancel_someone_elses(): void
    {
        $mine = $this->account($this->patient());
        $other = $this->patient();
        $theirs = Appointment::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $other->id, 'appointment_date' => now()->addDays(3)->toDateString(), 'appointment_time' => '10:00']);
        $headers = $this->bearer($mine);

        $this->getJson('/api/v1/portal/appointments', $headers)->assertOk()->assertJsonCount(0, 'data.upcoming');
        $this->postJson("/api/v1/portal/appointments/{$theirs->public_id}/cancel", [], $headers)->assertNotFound();
        $this->assertSame('scheduled', $theirs->fresh()->status);
    }

    public function test_cancelling_and_rescheduling_respect_the_cutoff(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $provider = $this->provider();
        $slot = $this->slot($provider, '+2 days 10:00');
        $first = $this->postJson('/api/v1/portal/appointments', ['slot' => Crypt::encryptString((string) $slot->id)], $headers)->assertCreated()->json('data.id');

        $next = $this->slot($provider, '+3 days 10:00');
        $this->postJson("/api/v1/portal/appointments/$first/reschedule", ['slot' => Crypt::encryptString((string) $next->id)], $headers)->assertOk();
        $this->assertSame('cancelled', Appointment::query()->where('public_id', $first)->first()->status);
        $this->assertSame(0, $slot->fresh()->booked_count, 'the old slot is free again');

        $soon = Appointment::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'appointment_date' => now()->toDateString(), 'appointment_time' => '12:00']);
        $this->postJson("/api/v1/portal/appointments/{$soon->public_id}/cancel", [], $headers)->assertStatus(409);
    }

    public function test_slots_for_a_provider_come_back_as_opaque_tokens(): void
    {
        $provider = $this->provider();
        $this->slot($provider, '+1 day 10:00');
        $headers = $this->bearer($this->account());
        $providers = $this->getJson('/api/v1/portal/providers', $headers)->assertOk()->json('data');
        $this->assertCount(1, $providers);
        $slots = $this->getJson("/api/v1/portal/providers/{$providers[0]['id']}/slots?date=".now()->addDay()->toDateString(), $headers)->assertOk()->json('data');
        $this->assertCount(1, $slots);
        $this->assertStringNotContainsString('"id"', json_encode($slots));
    }

    public function test_the_digital_queue_shows_people_ahead_but_never_who_they_are(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $provider = $this->provider();
        $mk = fn ($p, int $no) => tap(Appointment::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $p->id, 'provider_id' => $provider->id,
            'appointment_date' => now()->toDateString(), 'appointment_time' => '15:00', 'status' => 'checked_in']), fn ($a) => $a->token()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'token_number' => $no, 'status' => 'waiting', 'generated_at' => now()]));
        $mk($this->patient(), 1);
        $mk($this->patient(), 2);
        $mine = $mk($patient, 3);

        $res = $this->getJson("/api/v1/portal/appointments/{$mine->public_id}/queue", $headers)->assertOk();
        $this->assertSame(2, $res->json('data.ahead'));
        $this->assertSame(3, $res->json('data.token'));
        $this->assertArrayNotHasKey('patient', $res->json('data'));
    }

    public function test_viewing_a_result_is_written_to_the_access_log(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $report = $this->labReport($patient);
        app(ResultReleaseService::class)->sync();
        $row = PortalResultRelease::query()->first();
        app(ResultReleaseService::class)->release($row, $this->actor(['portal.release.manage']));

        $this->getJson("/api/v1/portal/results/{$row->public_id}", $this->bearer($account))->assertOk()->assertJsonPath('data.rows.0.test', 'Hemoglobin');
        $this->assertSame(1, PortalAccessLog::query()->where(['action' => 'view_result', 'patient_id' => $patient->id])->count());
    }
}
