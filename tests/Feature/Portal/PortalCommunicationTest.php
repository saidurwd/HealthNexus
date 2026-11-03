<?php

namespace Tests\Feature\Portal;

use App\Models\PatientConsent;
use App\Models\Portal\PortalCommPreference;
use App\Models\Portal\PortalNotification;
use App\Models\Portal\PortalOutbox;
use App\Services\Portal\CommunicationService;
use App\Services\Portal\PortalException;
use Carbon\Carbon;

class PortalCommunicationTest extends PortalTestCase
{
    private function send(string $purpose, string $channel = 'sms', ?int $patientId = null, string $body = 'Hello'): PortalOutbox
    {
        return app(CommunicationService::class)->send(['organization_id' => $this->organization->id, 'patient_id' => $patientId, 'channel' => $channel, 'purpose' => $purpose, 'template' => 'test', 'to' => '8801712345678', 'body' => $body]);
    }

    public function test_marketing_needs_an_explicit_opt_in_and_service_messages_are_on_until_switched_off(): void
    {
        $patient = $this->patient();
        $svc = app(CommunicationService::class);

        $this->assertSame('suppressed', $this->send('marketing', 'sms', $patient->id)->status);
        $this->assertNotSame('suppressed', $this->send('service', 'sms', $patient->id)->status);

        $svc->setPreference($patient, 'sms', 'marketing', true);
        $this->travel(2)->hours(); // a fresh, non-quiet moment: 12:00 UTC is 18:00 in Dhaka
        $this->assertNotSame('suppressed', $this->send('marketing', 'sms', $patient->id)->status);

        $svc->setPreference($patient, 'sms', 'service', false);
        $this->assertSame('suppressed', $this->send('service', 'sms', $patient->id)->status);
    }

    public function test_messages_about_ones_own_care_cannot_be_switched_off(): void
    {
        $this->expectException(PortalException::class);
        app(CommunicationService::class)->setPreference($this->patient(), 'sms', 'transactional', false);
    }

    public function test_stop_switches_off_everything_that_is_not_essential(): void
    {
        $patient = $this->patient();
        $svc = app(CommunicationService::class);
        $svc->setPreference($patient, 'email', 'marketing', true);
        $svc->optOutAll($patient);

        foreach (['sms', 'email', 'push', 'inapp'] as $channel) {
            $this->assertSame('suppressed', $this->send('service', $channel, $patient->id)->status, $channel);
            $this->assertSame('suppressed', $this->send('marketing', $channel, $patient->id)->status, $channel);
        }
        $this->assertNotSame('suppressed', $this->send('transactional', 'sms', $patient->id)->status);
    }

    public function test_quiet_hours_hold_a_text_until_the_morning_but_codes_go_at_once(): void
    {
        $patient = $this->patient();
        Carbon::setTestNow('2026-09-10 17:30:00'); // 23:30 in Dhaka
        $held = $this->send('service', 'sms', $patient->id);
        $this->assertSame('deferred', $held->status);
        $this->assertSame('2026-09-11 02:00:00', $held->send_after->utc()->format('Y-m-d H:i:s')); // 08:00 in Dhaka
        $this->assertNotSame('deferred', $this->send('transactional', 'sms', $patient->id)->status);

        $this->assertSame(0, app(CommunicationService::class)->releaseDeferred());
        Carbon::setTestNow('2026-09-11 02:05:00');
        $this->assertSame(1, app(CommunicationService::class)->releaseDeferred());
        $this->assertNotSame('deferred', $held->fresh()->status);
    }

    public function test_a_choice_made_during_quiet_hours_is_respected_when_the_message_is_finally_sent(): void
    {
        $patient = $this->patient();
        Carbon::setTestNow('2026-09-10 17:30:00');
        $held = $this->send('service', 'sms', $patient->id);
        app(CommunicationService::class)->setPreference($patient, 'sms', 'service', false);

        Carbon::setTestNow('2026-09-11 02:05:00');
        app(CommunicationService::class)->releaseDeferred();
        $this->assertSame('suppressed', $held->fresh()->status);
    }

    public function test_marketing_is_capped_per_week(): void
    {
        $patient = $this->patient();
        app(CommunicationService::class)->setPreference($patient, 'inapp', 'marketing', true);
        $results = [];
        for ($i = 0; $i < 4; $i++) {
            $results[] = $this->send('marketing', 'inapp', $patient->id)->status;
        }
        $this->assertSame(['sent', 'sent', 'suppressed', 'suppressed'], $results);
    }

    public function test_an_in_app_message_lands_in_the_notification_centre(): void
    {
        $patient = $this->patient();
        $this->send('service', 'inapp', $patient->id, 'Your follow-up is due');
        $this->assertSame(1, PortalNotification::query()->where('patient_id', $patient->id)->count());
    }

    public function test_the_api_shows_and_saves_preferences_and_never_lets_transactional_off(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));

        $this->getJson('/api/v1/portal/preferences', $headers)->assertOk()->assertJsonPath('data.sms.marketing', false)->assertJsonPath('data.sms.transactional', true);
        $this->putJson('/api/v1/portal/preferences', ['channel' => 'sms', 'purpose' => 'marketing', 'opted_in' => true], $headers)->assertOk()->assertJsonPath('data.sms.marketing', true);
        $this->putJson('/api/v1/portal/preferences', ['channel' => 'sms', 'purpose' => 'transactional', 'opted_in' => false], $headers)->assertStatus(422);
        $this->assertSame(1, PortalCommPreference::query()->where('patient_id', $patient->id)->count());
    }

    public function test_consents_are_granted_and_withdrawn_with_versions_kept(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));

        $this->putJson('/api/v1/portal/consents', ['type' => 'research', 'granted' => true], $headers)->assertOk();
        $this->putJson('/api/v1/portal/consents', ['type' => 'research', 'granted' => false], $headers)->assertOk();
        $this->putJson('/api/v1/portal/consents', ['type' => 'research', 'granted' => true], $headers)->assertOk();
        $this->putJson('/api/v1/portal/consents', ['type' => 'made_up', 'granted' => true], $headers)->assertStatus(422);

        $rows = PatientConsent::query()->where('patient_id', $patient->id)->orderBy('version')->get();
        $this->assertSame([1, 2], $rows->pluck('version')->all());
        $this->assertSame(['withdrawn', 'active'], $rows->pluck('status')->all());
    }
}
