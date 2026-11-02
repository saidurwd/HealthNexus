<?php

namespace Tests\Feature\Facility;

use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Patient;
use App\Models\User;
use App\Services\Facility\FacilityInstaller;
use App\Services\Ipd\IpdBedAllocationService;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Ipd\IpdTestCase;

/** A hospital with wards and beds (the IPD fixtures) plus staff who hold exactly the facility rights a test names. */
abstract class FacilityTestCase extends IpdTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-10 10:00:00');
        app(FacilityInstaller::class)->install($this->organization->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @param list<string> $permissions */
    protected function actor(array $permissions): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $user->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->refresh();
    }

    protected function admit(?IpdBed $bed = null): IpdAdmission
    {
        $bed ??= $this->makeBed();
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $encounter = Encounter::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id]);
        $admission = IpdAdmission::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'admission_number' => 'ADM-'.uniqid(), 'patient_id' => $patient->id, 'encounter_id' => $encounter->id, 'admitted_at' => now(), 'status' => 'admitted']);
        app(IpdBedAllocationService::class)->allocate($admission, $bed, $this->user);

        return $admission->fresh();
    }
}
