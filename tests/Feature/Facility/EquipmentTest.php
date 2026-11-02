<?php

namespace Tests\Feature\Facility;

use App\Events\Ipd\BedReleased;
use App\Models\Facility\FacAsset;
use App\Models\Facility\FacCleaningTask;
use App\Models\Facility\FacWorkOrder;
use App\Models\Ipd\IpdBedAllocation;
use App\Services\Facility\AssetService;
use App\Services\Facility\FacilityCheck;
use App\Services\Facility\HousekeepingService;
use App\Services\Facility\MaintenanceService;
use App\Services\Ipd\IpdBedAllocationService;
use App\Services\ModuleManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EquipmentTest extends FacilityTestCase
{
    private function asset(array $o = []): FacAsset
    {
        return app(AssetService::class)->register($this->organization->id, array_merge(['name' => 'Ventilator', 'category' => 'biomedical', 'risk_class' => 'high', 'location' => 'ICU'], $o), $this->user);
    }

    public function test_equipment_gets_a_tag_a_qr_token_and_a_label(): void
    {
        $a = $this->asset();
        $this->assertMatchesRegularExpression('/^EQ-\d{5}$/', $a->tag);
        $this->assertSame(32, strlen($a->qr_token));
        $this->assertStringContainsString('<svg', app(AssetService::class)->qrSvg($a));
        $this->assertSame($a->id, app(AssetService::class)->findByToken($a->qr_token, $this->organization->id)->id);
        $this->assertNull(app(AssetService::class)->findByToken($a->qr_token, $this->organization->id + 99), 'another hospital does not see it');
        $this->assertNull(app(AssetService::class)->findByToken('not-a-token', $this->organization->id));
        $this->expectException(ValidationException::class);
        $this->asset(['tag' => $a->tag]);
    }

    public function test_a_breakdown_takes_high_risk_equipment_out_of_use_and_the_repair_is_checked_by_someone_else(): void
    {
        $svc = app(MaintenanceService::class);
        $a = $this->asset();
        $engineer = $this->actor(['facility.workorder.manage']);
        $checker = $this->actor(['facility.workorder.verify']);
        $wo = $svc->reportBreakdown($a, $this->actor(['facility.workorder.report']), 'The alarm sounds all the time and the screen flickers.');
        $this->assertSame('out_of_service', $a->fresh()->status);
        $this->assertFalse(app(AssetService::class)->usable($a->fresh()));
        $this->assertSame('urgent', $wo->priority);
        $svc->start($wo, $engineer);
        $svc->addPart($wo->fresh(), 'Display cable', 1, 3500);
        Carbon::setTestNow('2026-09-10 14:30:00');
        $done = $svc->complete($wo->fresh(), $engineer, 'Replaced the display cable and tested for an hour.');
        $this->assertSame(['done', 270, '3500.00'], [$done->status, $done->downtime_minutes, $done->cost]);
        $this->assertSame('out_of_service', $a->fresh()->status, 'not back until a second person checks it');
        try {
            $svc->verify($done, $engineer);
            $this->fail('the engineer cannot check their own work');
        } catch (ValidationException) {
        }
        $svc->verify($done->fresh(), $checker);
        $this->assertSame('in_service', $a->fresh()->status);
        $this->assertTrue(app(AssetService::class)->usable($a->fresh()));
    }

    public function test_a_low_risk_repair_needs_no_second_person(): void
    {
        $svc = app(MaintenanceService::class);
        $a = $this->asset(['name' => 'Office chair', 'category' => 'furniture', 'risk_class' => 'low']);
        $wo = $svc->reportBreakdown($a, $this->user, 'The seat height lever is stuck.');
        $this->assertSame('under_maintenance', $a->fresh()->status);
        $svc->complete($wo, $this->user, 'Lubricated and tested the lever.');
        $this->assertSame('in_service', $a->fresh()->status);
        $this->expectException(ValidationException::class);
        $svc->verify($wo->fresh(), $this->actor(['facility.workorder.verify']));
    }

    public function test_a_plan_opens_a_work_order_before_it_is_due_and_moves_on_when_done(): void
    {
        $svc = app(MaintenanceService::class);
        $a = $this->asset(['name' => 'Autoclave', 'risk_class' => 'medium']);
        $plan = $svc->plan($a, 'preventive', 'Quarterly service', 90, '2026-09-15');
        $this->assertSame(1, $svc->openDue($this->organization->id), 'inside the 7-day lead time');
        $this->assertSame(0, $svc->openDue($this->organization->id), 'one order per plan');
        $wo = FacWorkOrder::query()->first();
        $this->assertSame(['preventive', 'normal', '2026-09-15'], [$wo->kind, $wo->priority, $wo->due_on->toDateString()]);
        $svc->complete($wo, $this->user, 'Serviced, seals changed, test cycle passed.');
        $this->assertSame('2026-12-09', $plan->fresh()->next_due_on->toDateString());
        $this->assertSame('2026-09-10', $plan->fresh()->last_done_on->toDateString());
    }

    public function test_a_failed_calibration_blocks_the_equipment_until_a_pass(): void
    {
        $svc = app(MaintenanceService::class);
        $a = $this->asset(['name' => 'Infusion pump']);
        $svc->plan($a, 'calibration', 'Pump calibration', 180, '2026-12-01');
        $svc->calibrate($a, $this->user, 'fail', '2026-09-10', null, 'CAL-1', 'Metrology Ltd', null, 'Flow rate 12% out.');
        $this->assertSame('out_of_service', $a->fresh()->status);
        $this->assertSame('Out of service', app(AssetService::class)->reason($a->fresh()));
        $this->assertSame(1, FacWorkOrder::query()->where(['asset_id' => $a->id, 'kind' => 'breakdown', 'priority' => 'urgent'])->count());
        $wo = FacWorkOrder::query()->first();
        $svc->complete($wo, $this->user, 'Recalibrated the pump and cleaned the sensor.');
        $this->assertSame('out_of_service', $a->fresh()->status, 'a failed calibration is still the last word');
        $svc->calibrate($a, $this->user, 'pass', '2026-09-10', null, 'CAL-2', 'Metrology Ltd');
        $svc->verify($wo->fresh(), $this->actor(['facility.workorder.verify']));
        $this->assertSame('in_service', $a->fresh()->status);
    }

    public function test_overdue_calibration_blocks_high_risk_equipment_only(): void
    {
        $svc = app(MaintenanceService::class);
        $high = $this->asset();
        $mid = $this->asset(['name' => 'Scale', 'risk_class' => 'medium']);
        $svc->plan($high, 'calibration', 'Calibrate', 90, '2026-09-01');
        $svc->plan($mid, 'calibration', 'Calibrate', 90, '2026-09-01');
        $this->assertSame('Calibration is overdue', app(AssetService::class)->reason($high));
        $this->assertTrue(app(AssetService::class)->usable($mid));
        $this->assertSame(1, $svc->alertDue($this->organization->id)['calibrations']);
    }

    public function test_work_orders_and_calibrations_are_validated(): void
    {
        $svc = app(MaintenanceService::class);
        $a = $this->asset(['risk_class' => 'low']);
        $wo = $svc->reportBreakdown($a, $this->user, 'Something is broken here.');
        foreach ([fn () => $svc->complete($wo, $this->user, 'x'), fn () => $svc->calibrate($a, $this->user, 'maybe', '2026-09-10', '2027-01-01'), fn () => $svc->calibrate($a, $this->user, 'pass', '2026-09-20', '2027-01-01'),
            fn () => $svc->calibrate($a, $this->user, 'pass', '2026-09-10'), fn () => $svc->plan($a, 'preventive', 'x', 0), fn () => $svc->addPart($wo, 'Part', 0)] as $bad) {
            try {
                $bad();
                $this->fail('accepted');
            } catch (ValidationException) {
            }
        }
        $cal = $this->asset(['name' => 'Monitor']);
        $order = $svc->create($this->organization->id, ['asset_id' => $cal->id, 'kind' => 'calibration', 'title' => 'Calibrate monitor'], $this->user);
        $this->expectException(ValidationException::class);
        $svc->complete($order, $this->user, 'Done without recording the result.'); // a calibration order needs its result first
    }

    public function test_a_released_bed_in_cleaning_gets_one_task_and_cleaning_makes_it_available(): void
    {
        $bed = $this->makeBed();
        $admission = $this->admit($bed);
        $alloc = IpdBedAllocation::query()->where('admission_id', $admission->id)->first();
        app(IpdBedAllocationService::class)->release($alloc, $this->user, 'Discharged', true);
        $this->assertSame('cleaning', $bed->fresh()->status);
        $task = FacCleaningTask::query()->first();
        $this->assertNotNull($task, 'the listener made the task');
        $this->assertSame(['bed_turnover', 'pending'], [$task->kind, $task->status]);
        $this->assertSame(1, count(FacCleaningTask::all()));
        $this->assertNull(app(HousekeepingService::class)->onBedReleased($alloc->fresh()), 'a repeat makes nothing');
        $hk = $this->actor(['facility.housekeeping.work']);
        app(HousekeepingService::class)->start($task, $hk);
        try {
            app(HousekeepingService::class)->complete($task->fresh(), $hk, [0, 1]);
            $this->fail('the whole checklist is needed');
        } catch (ValidationException) {
        }
        Carbon::setTestNow('2026-09-10 10:35:00');
        $done = app(HousekeepingService::class)->complete($task->fresh(), $hk, range(0, count($task->checklist) - 1));
        $this->assertSame(['done', 35, true], [$done->status, $done->turnaround_minutes, $done->bed_released]);
        $this->assertSame('available', $bed->fresh()->status);
    }

    public function test_an_isolation_clean_is_inspected_by_a_second_person_before_the_bed_is_released(): void
    {
        $bed = $this->makeBed();
        $admission = $this->admit($bed);
        DB::table('qa_isolations')->insert(['public_id' => (string) Str::ulid(), 'organization_id' => $this->organization->id, 'patient_id' => $admission->patient_id, 'precaution' => 'contact', 'reason' => 'MRSA', 'started_at' => now()->addMinute(), 'created_at' => now(), 'updated_at' => now()]);
        $alloc = IpdBedAllocation::query()->where('admission_id', $admission->id)->first();
        $alloc->update(['allocated_at' => now()->subDay()]);
        app(IpdBedAllocationService::class)->release($alloc, $this->user, 'Discharged', true);
        $task = FacCleaningTask::query()->first();
        $this->assertSame(['isolation_terminal', 'urgent'], [$task->kind, $task->priority]);
        $svc = app(HousekeepingService::class);
        $hk = $this->actor(['facility.housekeeping.work']);
        $svc->complete($task, $hk, range(0, count($task->checklist) - 1));
        $this->assertSame('cleaning', $bed->fresh()->status, 'not yet: it needs inspecting');
        try {
            $svc->inspect($task->fresh(), $hk, true);
            $this->fail('the cleaner cannot inspect their own work');
        } catch (ValidationException) {
        }
        $svc->inspect($task->fresh(), $this->actor(['facility.housekeeping.inspect']), true);
        $this->assertSame('available', $bed->fresh()->status);
    }

    public function test_a_hospital_without_the_module_gets_no_tasks(): void
    {
        app(ModuleManager::class)->set('facility', false, $this->organization->id);
        $bed = $this->makeBed();
        $admission = $this->admit($bed);
        app(IpdBedAllocationService::class)->release(IpdBedAllocation::query()->where('admission_id', $admission->id)->first(), $this->user, 'Discharged', true);
        $this->assertSame(0, FacCleaningTask::query()->count());
        Event::fake([BedReleased::class]);
        $this->assertSame('cleaning', $bed->fresh()->status);
    }

    public function test_a_cleaning_audit_scores_an_area_and_warns_when_poor(): void
    {
        $audit = app(HousekeepingService::class)->audit($this->organization->id, 'Ward 3', [['item' => 'Floors', 'ok' => true], ['item' => 'Bed rails', 'ok' => false], ['item' => 'Sinks', 'ok' => true], ['item' => 'Bins', 'ok' => false]], $this->user);
        $this->assertSame([50.0, ['Bed rails', 'Bins']], [(float) $audit->score, $audit->findings]);
        $this->expectException(ValidationException::class);
        app(HousekeepingService::class)->audit($this->organization->id, '', [], $this->user);
    }

    public function test_the_daily_check_runs_and_is_safe_to_repeat(): void
    {
        $a = $this->asset(['risk_class' => 'medium']);
        app(MaintenanceService::class)->plan($a, 'preventive', 'Monthly check', 30, '2026-09-12');
        $r = app(FacilityCheck::class)->run($this->organization->id);
        $this->assertSame(1, $r['work_orders_opened']);
        $this->assertSame(0, app(FacilityCheck::class)->run($this->organization->id)['work_orders_opened']);
        $this->assertSame(1, app(FacilityCheck::class)->runAll());
    }
}
