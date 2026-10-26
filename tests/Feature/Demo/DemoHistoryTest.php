<?php

namespace Tests\Feature\Demo;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Encounter;
use App\Models\Organization;
use App\Models\Patient;
use Carbon\Carbon;
use Database\Seeders\BillingSeeder;
use Database\Seeders\ClinicalMasterDataSeeder;
use Database\Seeders\IcuSeeder;
use Database\Seeders\IpdSeeder;
use Database\Seeders\LabSeeder;
use Database\Seeders\NursingSeeder;
use Database\Seeders\OtSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PharmacySeeder;
use Database\Seeders\RadiologySeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Runs the whole demo history (AI-RULES 16) for one month at a small volume on a freshly seeded organization, and checks each module
 * has data spread over the period. Every phase adds its module's assertions here.
 */
class DemoHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create(['code' => 'DEMOH']);
        Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->seed([PermissionSeeder::class, BillingSeeder::class, LabSeeder::class, RadiologySeeder::class, PharmacySeeder::class, IpdSeeder::class, NursingSeeder::class, OtSeeder::class, IcuSeeder::class, WorkflowSeeder::class, ClinicalMasterDataSeeder::class]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function run_(): string
    {
        $this->assertSame(0, Artisan::call('demo:history', ['organization' => 'DEMOH', '--months' => 1, '--visits-per-day' => 2, '--password' => 'demo-pass-123']));

        return Artisan::output();
    }

    public function test_history_is_created_across_the_period_with_test_users(): void
    {
        $output = $this->run_();

        $this->assertStringContainsString('superuser@demoh.example.invalid', $output);
        $this->assertGreaterThan(20, Patient::where('organization_id', $this->organization->id)->count());
        $this->assertGreaterThan(20, Appointment::where('organization_id', $this->organization->id)->count());
        $this->assertGreaterThan(15, Encounter::where('organization_id', $this->organization->id)->where('status', 'locked')->orWhere('status', 'completed')->count());

        $dates = Encounter::where('organization_id', $this->organization->id)->pluck('encounter_date')->map(fn ($d) => Carbon::parse($d));
        $this->assertGreaterThan(20, $dates->min()->diffInDays($dates->max()), 'encounters are spread over the month');
        $this->assertTrue($dates->max()->lt(now()->startOfDay()), 'history ends before today');
    }

    public function test_it_can_be_run_again_without_failing(): void
    {
        $this->run_();
        $this->run_();
        $this->assertTrue(true);
    }

    public function test_production_is_refused(): void
    {
        $this->app['env'] = 'production';
        $this->assertSame(1, Artisan::call('demo:history', ['organization' => 'DEMOH']));
    }
}
