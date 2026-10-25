<?php

namespace Tests\Feature\Phase0;

use App\Models\Ipd\IpdBed;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoBuildTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_command_builds_a_complete_training_organization(): void
    {
        $this->artisan('demo:build', ['--patients' => 60, '--password' => 'Training-Only-1'])->assertExitCode(0);

        $organization = Organization::where('code', 'like', 'TRAIN%')->firstOrFail();
        $this->assertTrue($organization->settings['training']);
        $this->assertSame(60, Patient::where('organization_id', $organization->id)->count());
        $this->assertGreaterThan(0, Patient::where('organization_id', $organization->id)->where('first_name', 'রহিম')->count(), 'Bangla names are present');
        $this->assertSame(20, IpdBed::where('organization_id', $organization->id)->count());
        $this->assertSame(17, DB::table('billing_items')->where('organization_id', $organization->id)->count());
        $this->assertSame(8, DB::table('pharmacy_batches')->where('organization_id', $organization->id)->count());
        $this->assertSame(8, (int) DB::table('stock_ledger')->where('organization_id', $organization->id)->where('txn_type', 'opening_balance')->count());
        $this->assertSame(7, User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'hospital_admin'))->where('email', 'like', 'training.staff%')->count());
    }

    public function test_building_twice_creates_two_separate_organizations(): void
    {
        $this->artisan('demo:build', ['--patients' => 5])->assertExitCode(0);
        sleep(1);
        $this->artisan('demo:build', ['--patients' => 5, '--retire-old' => true])->assertExitCode(0);

        $this->assertSame(2, Organization::where('code', 'like', 'TRAIN%')->count());
        $this->assertSame(1, Organization::where('code', 'like', 'TRAIN%')->where('is_active', true)->count());
    }

    public function test_it_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('demo:build')->assertExitCode(1);
        $this->assertSame(0, Organization::where('code', 'like', 'TRAIN%')->count());
    }
}
