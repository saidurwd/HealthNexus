<?php

namespace Tests\Feature\Analytics;

use App\Models\Analytics\AnalyticsLoad;
use App\Models\Analytics\AnalyticsSource;
use App\Models\Department;
use App\Models\Patient;
use App\Services\Analytics\LoadRunner;
use App\Services\Analytics\Warehouse;
use Illuminate\Support\Facades\Schema;

class WarehouseCoreTest extends AnalyticsTestCase
{
    public function test_dimensions_load_and_a_second_run_changes_nothing(): void
    {
        $this->patient();
        $this->provider();
        $r = $this->runLoads();
        foreach (['dim_date', 'dim_branch', 'dim_department', 'dim_provider', 'dim_patient'] as $key) {
            $this->assertSame('ok', $r[$key]->status, $key.': '.$r[$key]->error);
        }
        $this->assertSame(1, $this->wh('dim_branch')->count());
        $this->assertGreaterThan(700, $this->wh('dim_date')->count());
        $this->assertSame(1, $this->wh('dim_patient')->where('is_current', true)->count());
        $this->assertSame(0, $this->wh('dim_date')->where('date_key', 20260910)->where('is_weekend', true)->count(), '10 Sep 2026 is a Thursday');
        $this->assertSame(1, $this->wh('dim_date')->where('date_key', 20260911)->where('is_weekend', true)->count(), 'Friday is a weekend day');
        $again = $this->runLoads(['dim_patient', 'dim_branch']);
        $this->assertSame(0, $again['dim_patient']->rows_written, 'nothing changed, so nothing is written');
        $this->assertSame(1, $this->wh('dim_patient')->count());
        $this->assertSame(2, AnalyticsLoad::query()->where('source_key', 'dim_patient')->count(), 'both runs are recorded');
    }

    public function test_the_warehouse_holds_no_name_phone_or_patient_id(): void
    {
        $p = $this->patient(['first_name' => 'Rina', 'last_name' => 'Akter', 'phone' => '01712345678', 'city' => 'Dhaka']);
        $this->runLoads(['dim_patient']);
        $row = (array) $this->wh('dim_patient')->first();
        $this->assertSame(['patient_key', 'pseudonym', 'organization_id', 'age_band', 'sex', 'district', 'patient_status', 'deceased', 'valid_from', 'valid_to', 'is_current', 'attr_hash'], array_keys($row));
        $dump = json_encode($row);
        foreach (['Rina', 'Akter', '01712345678'] as $secret) {
            $this->assertStringNotContainsString($secret, $dump);
        }
        $this->assertSame(app(Warehouse::class)->pseudonym($p->id, $p->organization_id), $row['pseudonym']);
        $this->assertNotSame(app(Warehouse::class)->pseudonym($p->id, $p->organization_id), app(Warehouse::class)->pseudonym($p->id + 1, $p->organization_id));
        config(['analytics.pseudonym_key' => 'another-key']);
        $this->assertNotSame($row['pseudonym'], app(Warehouse::class)->pseudonym($p->id, $p->organization_id), 'a different key gives different pseudonyms: the id cannot be recovered without it');
    }

    public function test_patient_dimension_keeps_history_when_a_description_changes(): void
    {
        $p = $this->patient(['city' => 'Dhaka']);
        $this->runLoads(['dim_patient']);
        $this->travelTo(now()->addDays(3));
        Patient::query()->whereKey($p->id)->update(['city' => 'Chattogram', 'updated_at' => now()]);
        $this->runLoads(['dim_patient']);
        $versions = $this->wh('dim_patient')->orderBy('patient_key')->get();
        $this->assertCount(2, $versions);
        $this->assertSame('Dhaka', $versions[0]->district);
        $this->assertSame(0, (int) $versions[0]->is_current);
        $this->assertNotNull($versions[0]->valid_to);
        $this->assertSame('Chattogram', $versions[1]->district);
        $this->assertSame(1, (int) $versions[1]->is_current);
        $this->assertSame($versions[0]->valid_to, $versions[1]->valid_from, 'one version ends the day the next begins');
    }

    public function test_a_failed_loader_is_recorded_and_the_others_still_run_and_the_watermark_stays(): void
    {
        $this->patient();
        Department::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'name' => 'OPD', 'code' => 'OPD', 'is_active' => true]);
        $this->runLoads(['dim_branch']);
        $before = AnalyticsSource::query()->where('key', 'dim_branch')->value('watermark');
        $this->assertNotNull($before);
        Schema::connection(null)->rename('dim_department', 'dim_department_gone');
        $r = $this->runLoads(['dim_department', 'dim_provider']);
        Schema::connection(null)->rename('dim_department_gone', 'dim_department');
        $this->assertSame('failed', $r['dim_department']->status);
        $this->assertNotEmpty($r['dim_department']->error);
        $this->assertSame('ok', $r['dim_provider']->status);
        $this->assertSame('failed', AnalyticsSource::query()->where('key', 'dim_department')->value('last_status'));
        $this->assertNull(AnalyticsSource::query()->where('key', 'dim_department')->value('watermark'), 'a failed run does not move the watermark');
        $this->assertSame(['dim_department'], app(LoadRunner::class)->problems()->where('last_status', 'failed')->pluck('key')->all());
        $ok = $this->runLoads(['dim_department']);
        $this->assertSame('ok', $ok['dim_department']->status, 'the next run does the work again');
    }

    public function test_rebuild_empties_the_warehouse_and_loads_it_again(): void
    {
        $this->patient();
        $this->runLoads();
        $this->wh('dim_branch')->update(['name' => 'tampered']);
        app(LoadRunner::class)->rebuild();
        $this->assertNotSame('tampered', $this->wh('dim_branch')->value('name'));
        $this->assertSame(1, $this->wh('dim_patient')->count());
    }
}
