<?php

namespace Tests\Feature\Ed;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Billing\ChargeEventLog;
use App\Models\Branch;
use App\Models\Ed\EdVisit;
use App\Models\Encounter;
use App\Models\Organization;
use App\Reports\EdVisitsReport;
use App\Services\Ed\EdDispositionService;
use App\Services\Ed\EdKpiService;
use App\Services\Ed\EdOrderService;
use App\Services\Ed\EdResuscitationService;
use App\Services\Ed\EdTriageService;
use App\Services\Ed\EdVisitService;
use App\Services\Reports\ReportContext;
use Illuminate\Support\Str;

class EdReportingTest extends EdTestCase
{
    private function visit(): EdVisit
    {
        return app(EdVisitService::class)->arrive($this->user, $this->branch, ['patient_id' => $this->patient()->id, 'arrival_mode' => 'walk_in']);
    }

    private function close(EdVisit $visit, string $type = 'discharge'): void
    {
        app(EdDispositionService::class)->decide($visit, $this->user, ['type' => $type]);
    }

    public function test_the_visit_fee_is_announced_once_and_resus_only_when_used(): void
    {
        $a = $this->visit();
        app(EdTriageService::class)->triage($a, $this->user, ['level' => 3]);
        $this->close($a);
        $b = $this->visit();
        app(EdResuscitationService::class)->record($b, $this->user, 'cpr_started');
        $this->close($b);

        $this->assertSame(2, ChargeEventLog::where('idempotency_key', 'like', 'ED:%:ED-VISIT:%')->count());
        $this->assertSame(1, ChargeEventLog::where('idempotency_key', 'like', 'ED:%:ED-RESUS:%')->count());
    }

    public function test_a_priced_item_produces_exactly_one_charge(): void
    {
        $category = BillingCategory::create(['organization_id' => $this->organization->id, 'branch_id' => null, 'name' => 'Emergency', 'code' => 'ED', 'is_active' => true]);
        BillingItem::create([
            'organization_id' => $this->organization->id, 'branch_id' => null, 'category_id' => $category->id, 'item_code' => 'ED-VISIT', 'item_type' => 'service',
            'name' => 'Emergency Visit', 'base_price' => '800.00', 'is_taxable' => false, 'is_active' => true,
        ]);
        $visit = $this->visit();
        app(EdTriageService::class)->triage($visit, $this->user, ['level' => 3]);
        $this->close($visit);

        $log = ChargeEventLog::where('idempotency_key', 'like', 'ED:%:ED-VISIT:%')->firstOrFail();
        $this->assertSame('accepted', $log->outcome);
        $this->assertNotNull($log->billing_charge_id);
    }

    public function test_lwbs_before_triage_is_not_charged_and_a_procedure_is_announced(): void
    {
        $left = $this->visit();
        $this->close($left, 'lwbs');
        $this->assertSame('not_chargeable', ChargeEventLog::where('idempotency_key', 'like', 'ED:%:ED-VISIT:%')->value('outcome'));

        $visit = $this->visit();
        app(EdOrderService::class)->recordProcedure($visit, $this->user, 'Suturing');
        $this->assertSame(1, ChargeEventLog::where('idempotency_key', 'like', 'ED:%:ED-PROC:%')->count());
    }

    public function test_kpis_measure_the_waits_and_lwbs_share(): void
    {
        $seen = $this->visit();
        app(EdTriageService::class)->triage($seen, $this->user, ['level' => 3]);
        $seen->update(['arrival_at' => now()->subMinutes(40), 'triaged_at' => now()->subMinutes(30), 'doctor_seen_at' => now()->subMinutes(10), 'doctor_due_at' => now()->subMinutes(10)]);
        $this->close($seen);
        $left = $this->visit();
        $this->close($left, 'lwbs');

        $kpi = app(EdKpiService::class)->summary($this->organization->id, $this->branch->id, now()->subDay(), now()->addMinute());

        $this->assertSame(2, $kpi['arrivals']);
        $this->assertSame(10, $kpi['door_to_triage']['median']);
        $this->assertSame(30, $kpi['door_to_doctor']['median']);
        $this->assertSame(50.0, $kpi['lwbs_percent']);
        $this->assertSame(100.0, $kpi['door_to_doctor_met_percent']);
        $this->assertSame(['discharge' => 1, 'lwbs' => 1], $kpi['by_disposition']);
    }

    public function test_the_export_report_lists_only_this_organizations_visits(): void
    {
        $this->visit();
        $other = Organization::factory()->create();
        EdVisit::query()->insert(['public_id' => Str::ulid(), 'organization_id' => $other->id, 'branch_id' => Branch::factory()->create(['organization_id' => $other->id])->id,
            'patient_id' => $this->patient()->id, 'encounter_id' => Encounter::factory()->create(['organization_id' => $this->organization->id])->id, 'visit_number' => 'X-1', 'arrival_at' => now(), 'arrival_mode' => 'walk_in', 'status' => 'waiting', 'created_at' => now(), 'updated_at' => now()]);

        $rows = (new EdVisitsReport)->rows(new ReportContext($this->user, $this->organization->id, $this->branch->id, []))->all();

        $this->assertCount(1, $rows);
    }

    public function test_dashboard_and_workspace_load(): void
    {
        $visit = $this->visit();
        $visit->update(['doctor_id' => $this->user->id]);
        $this->get('/admin/emergency/dashboard?days=30')->assertOk()->assertSee('Door to triage');
        $this->get('/admin/emergency/workspace')->assertOk()->assertSee($visit->visit_number);
        $this->get('/admin/emergency/dashboard?days=5')->assertSessionHasErrors('days');
    }
}
