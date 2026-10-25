<?php

namespace Tests\Feature\Icu;

use App\Models\Clinical\ClinicalAlert;
use App\Models\Notification;
use App\Models\User;
use App\Services\Clinical\Alerts\AlertReport;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use App\Services\Icu\IcuAlertService;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/** H-04: raise → deliver → acknowledge → escalate → resolve, plus the ICU adapter. */
class AlertServiceTest extends IcuTestCase
{
    private function request(array $o = []): AlertRequest
    {
        return new AlertRequest(...array_merge([
            'organizationId' => $this->organization->id, 'category' => 'critical_lab', 'severity' => 'critical',
            'message' => 'Potassium 6.8 mmol/L', 'sourceModule' => 'lab', 'recipientUserIds' => [$this->user->id],
        ], $o));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($this->organization->id, ['access_level' => 'staff']);
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }

    public function test_raise_notifies_the_named_recipients_and_schedules_the_first_escalation(): void
    {
        $alert = app(AlertService::class)->raise($this->request());

        $this->assertSame('open', $alert->status);
        $this->assertSame(1, $alert->recipients()->count());
        $this->assertTrue(Notification::where('user_id', $this->user->id)->where('message', 'Potassium 6.8 mmol/L')->exists());
        $this->assertTrue($alert->next_escalation_at->equalTo($alert->raised_at->copy()->addMinutes(10)));
    }

    public function test_recipients_from_another_organization_are_not_notified(): void
    {
        $outsider = User::factory()->create();

        $alert = app(AlertService::class)->raise($this->request(['recipientUserIds' => [$outsider->id]]));

        $this->assertSame(0, $alert->recipients()->count());
    }

    public function test_same_source_or_dedup_key_does_not_raise_twice(): void
    {
        $service = app(AlertService::class);

        $a = $service->raise($this->request(['sourceType' => 'lab_result', 'sourceId' => 9]));
        $b = $service->raise($this->request(['sourceType' => 'lab_result', 'sourceId' => 9]));
        $c = $service->raise($this->request(['category' => 'icu_deterioration', 'dedupKey' => 'p1:hr']));
        $d = $service->raise($this->request(['category' => 'icu_deterioration', 'dedupKey' => 'p1:hr']));

        $this->assertSame($a->id, $b->id);
        $this->assertSame($c->id, $d->id);
        $this->assertSame(2, ClinicalAlert::count());
    }

    public function test_critical_lab_alert_needs_read_back_and_cannot_be_acknowledged_twice(): void
    {
        $service = app(AlertService::class);
        $alert = $service->raise($this->request());

        try {
            $service->acknowledge($alert, $this->user);
            $this->fail('read-back should be required');
        } catch (ValidationException) {
        }

        $acked = $service->acknowledge($alert, $this->user, 'repeat sample sent', readBack: true);
        $this->assertSame('acknowledged', $acked->status);
        $this->assertNull($acked->next_escalation_at);

        $this->expectException(ValidationException::class);
        $service->acknowledge($alert, $this->user, readBack: true);
    }

    public function test_override_requires_a_reason(): void
    {
        $service = app(AlertService::class);
        $alert = $service->raise($this->request(['category' => 'allergy_warning', 'severity' => 'warning']));

        try {
            $service->override($alert, $this->user, '  ');
            $this->fail('reason is mandatory');
        } catch (ValidationException) {
        }

        $this->assertSame('overridden', $service->override($alert, $this->user, 'Patient tolerates it; documented')->status);
    }

    public function test_unacknowledged_alert_climbs_the_ladder_and_stops_when_acknowledged(): void
    {
        $service = app(AlertService::class);
        $charge = $this->userWithRole('charge_nurse');
        $supervisor = $this->userWithRole('nursing_supervisor');
        $alert = $service->raise($this->request(['category' => 'icu_deterioration']));

        $this->travel(9)->minutes();
        $this->assertSame(0, $service->escalateDue(), 'nothing due before 10 minutes');

        $this->travel(2)->minutes();
        $this->assertSame(1, $service->escalateDue());
        $this->assertTrue(Notification::where('user_id', $charge->id)->exists());
        $this->assertFalse(Notification::where('user_id', $supervisor->id)->exists());
        $this->assertSame(0, $service->escalateDue(), 'a level is never escalated twice');

        $this->travel(10)->minutes();
        $this->assertSame(1, $service->escalateDue());
        $this->assertTrue(Notification::where('user_id', $supervisor->id)->exists());
        $this->assertSame(2, $alert->fresh()->escalation_level);
        $this->assertSame(2, $alert->escalations()->count());

        $service->acknowledge($alert->fresh(), $this->user, readBack: true);
        $this->travel(60)->minutes();
        $this->assertSame(0, $service->escalateDue());
    }

    public function test_hospital_setting_overrides_the_default_ladder(): void
    {
        app(\App\Services\SettingsService::class)->set('alerts.escalation_policies', ['critical' => [['after_minutes' => 2, 'roles' => ['charge_nurse']]]]);
        $this->userWithRole('charge_nurse');
        $service = app(AlertService::class);
        $service->raise($this->request());

        $this->travel(3)->minutes();

        $this->assertSame(1, $service->escalateDue());
    }

    public function test_icu_alert_is_raised_through_the_service_and_acknowledgement_is_mirrored(): void
    {
        $episode = $this->makeEpisode();
        $icu = app(IcuAlertService::class);

        $legacy = $icu->trigger($episode, 'heart_rate_high', 'critical', '152', '50-130');
        $unified = ClinicalAlert::where('source_module', 'icu')->where('source_id', (string) $legacy->id)->firstOrFail();

        $this->assertSame('icu_deterioration', $unified->category);
        $this->assertSame($episode->patient_id, $unified->patient_id);
        $this->assertNull($unified->next_escalation_at, 'ICU keeps its own ladder for now');

        $icu->acknowledge($legacy, $this->user, 'reviewed');

        $this->assertSame('acknowledged', $unified->fresh()->status);
    }

    public function test_latency_report_and_unacknowledged_list(): void
    {
        $service = app(AlertService::class);
        $a = $service->raise($this->request(['category' => 'icu_deterioration']));
        $service->raise($this->request(['category' => 'icu_deterioration', 'message' => 'second']));
        $this->travel(5)->minutes();
        $service->acknowledge($a, $this->user, readBack: true);

        $report = app(AlertReport::class)->latency($this->organization->id, now()->subDay(), now()->addDay());

        $this->assertSame(2, $report['icu_deterioration']['raised']);
        $this->assertSame(1, $report['icu_deterioration']['acknowledged']);
        $this->assertSame(300, $report['icu_deterioration']['avg_ack_seconds']);
        $this->assertCount(1, app(AlertReport::class)->unacknowledged($this->organization->id));
    }
}
