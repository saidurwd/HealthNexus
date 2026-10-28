<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\Billing\BillingInvoice;
use App\Models\Portal\PortalOtp;
use App\Models\Portal\PortalOutbox;
use App\Models\Portal\PortalProxy;
use App\Models\Portal\PortalResultRelease;
use App\Models\Portal\PortalSecurityEvent;
use App\Services\Portal\CommunicationService;
use App\Services\Portal\MessagingService;
use App\Services\Portal\PortalAssistant;
use App\Services\Portal\PortalRetentionService;
use App\Services\Portal\PortalTelehealthService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class PortalOpsTest extends PortalTestCase
{
    public function test_the_nightly_check_gives_decisions_to_new_reports_and_releases_timed_ones(): void
    {
        config(['portal.release.default_mode' => 'delayed', 'portal.release.default_delay_hours' => 2]);
        $this->labReport($this->patient());

        $this->assertSame(0, Artisan::call('portal:check'));
        $this->assertSame(1, PortalResultRelease::query()->count());
        $this->assertSame('pending', PortalResultRelease::query()->first()->status);

        $this->travel(3)->hours();
        Artisan::call('portal:check');
        $this->assertSame('released', PortalResultRelease::query()->first()->status);
    }

    public function test_an_old_backlog_is_brought_in_only_on_purpose(): void
    {
        $report = $this->labReport($this->patient());
        $report->update(['generated_at' => now()->subDays(100)]);

        Artisan::call('portal:check');
        $this->assertSame(0, PortalResultRelease::query()->count(), 'nothing from 100 days ago on the first night');
        Artisan::call('portal:check', ['--since-days' => 200]);
        $this->assertSame(1, PortalResultRelease::query()->count());
    }

    public function test_the_check_ends_expired_approvals_and_removes_old_codes(): void
    {
        $parent = $this->account($this->patient());
        $child = $this->patient(['date_of_birth' => now()->subYears(5)->toDateString()]);
        PortalProxy::create(['organization_id' => $this->organization->id, 'account_id' => $parent->id, 'patient_id' => $child->id, 'relationship' => 'parent', 'scopes' => ['billing'], 'status' => 'active', 'valid_from' => now()->subYear()->toDateString(), 'valid_to' => now()->subDay()->toDateString()]);
        PortalOtp::create(['organization_id' => $this->organization->id, 'target' => '8801712345678', 'purpose' => 'login', 'code_hash' => 'x', 'expires_at' => now()->subDays(5), 'created_at' => now()->subDays(5)]);
        PortalSecurityEvent::create(['kind' => 'login_ok', 'created_at' => now()->subDays(400)]);

        Artisan::call('portal:check');
        $this->assertSame('expired', PortalProxy::query()->first()->status);
        $this->assertSame(0, PortalOtp::query()->count());
        $this->assertSame(0, PortalSecurityEvent::query()->where('kind', 'login_ok')->where('created_at', '<', now()->subDays(365))->count());
    }

    public function test_the_check_sends_texts_held_for_quiet_hours_once_morning_comes(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        Carbon::setTestNow('2026-09-10 17:30:00');
        $row = app(CommunicationService::class)->send(['organization_id' => $this->organization->id, 'patient_id' => $patient->id, 'channel' => 'sms', 'purpose' => 'service', 'template' => 'x', 'to' => '8801712345678', 'body' => 'hi']);
        $this->assertSame('deferred', $row->status);

        Carbon::setTestNow('2026-09-11 02:30:00');
        Artisan::call('portal:check');
        $this->assertNotSame('deferred', $row->fresh()->status);
    }

    public function test_video_is_a_hand_off_and_says_so_when_no_provider_is_installed(): void
    {
        $patient = $this->patient();
        $video = Appointment::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'is_telemedicine' => true, 'appointment_date' => now()->addDay()->toDateString(), 'appointment_time' => '10:00']);
        $visit = Appointment::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'is_telemedicine' => false, 'appointment_date' => now()->addDay()->toDateString(), 'appointment_time' => '11:00']);

        $this->assertFalse(app(PortalTelehealthService::class)->joinInfo($video)['available']);
        $this->assertStringContainsString('not turned on', app(PortalTelehealthService::class)->joinInfo($video)['message']);
        $this->assertStringContainsString('at the hospital', app(PortalTelehealthService::class)->joinInfo($visit)['message']);
        $headers = $this->bearer($this->account($patient));
        $this->getJson("/api/v1/portal/appointments/{$video->public_id}/video", $headers)->assertOk()->assertJsonPath('data.available', false);
    }

    public function test_the_assistant_hook_answers_nothing_today_and_never_touches_urgent_text(): void
    {
        $assistant = app(PortalAssistant::class);
        $this->assertNull($assistant->suggestReply('What are the visiting hours?'));
        $this->assertNull($assistant->suggestReply('I have severe chest pain'));
    }

    public function test_the_hospital_can_see_who_holds_a_login_and_closing_keeps_clinical_records(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $this->invoice($patient);
        app(PortalRetentionService::class)->closeOwn($account, 'Portal-Pass-123', 'moving away');

        $this->assertSame('closed', $account->fresh()->status);
        $this->assertSame(1, BillingInvoice::query()->where('patient_id', $patient->id)->count());
        $this->assertFalse($patient->fresh()->portal_enabled);
    }

    public function test_the_outbox_holds_the_sign_in_code_when_no_sms_provider_is_installed(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $this->postJson('/api/v1/portal/auth/register/start', ['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => '1990-04-12', 'phone' => '01712345678'])->assertOk();

        $row = PortalOutbox::query()->where('template', 'otp')->first();
        $this->assertSame('queued', $row->status);
        $this->assertSame('no SMS provider installed', $row->reason);
        $this->assertSame('transactional', $row->purpose);
    }

    public function test_the_config_endpoint_tells_the_app_what_it_needs_without_signing_in(): void
    {
        $this->getJson('/api/v1/portal/config')->assertOk()->assertJsonPath('data.api_version', 'v1')->assertJsonPath('data.emergency_number', '999')->assertJsonPath('data.payments_enabled', true);
        config(['portal.payments.driver' => 'null']);
        $this->getJson('/api/v1/portal/config')->assertJsonPath('data.payments_enabled', false);
    }

    public function test_lists_are_paged_and_the_page_size_is_capped(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $headers = $this->bearer($account);
        for ($i = 0; $i < 7; $i++) {
            app(MessagingService::class)->start($account, $patient, "Subject $i", 'general', 'hello');
        }
        $res = $this->getJson('/api/v1/portal/threads?per_page=3', $headers)->assertOk();
        $this->assertCount(3, $res->json('data'));
        $this->assertSame(7, $res->json('meta.total'));
        $this->assertSame(3, $res->json('meta.last_page'));
        $this->assertCount(7, $this->getJson('/api/v1/portal/threads?per_page=500', $headers)->json('data'));
    }
}
