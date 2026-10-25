<?php

namespace Tests\Feature\Ipd;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedType;
use App\Models\Ipd\IpdRoom;
use App\Models\Ipd\IpdWard;
use App\Models\Provider;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class IpdTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Organization $organization;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);

        $this->user = User::factory()->create();
        $this->user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->user->assignRole('super_admin');

        $this->actingAs($this->user);

        session()->put('tenant_organization_id', $this->organization->id);
        session()->put('tenant_branch_id', $this->branch->id);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
        app(TenantContextResolver::class)->setBranchId($this->branch->id);
    }

    protected function makeWard(string $code = 'MED', string $genderPolicy = 'any'): IpdWard
    {
        return IpdWard::create([
            'organization_id' => $this->organization->id, 'branch_id' => null,
            'code' => $code.'-'.uniqid(), 'name' => 'Medical Ward', 'gender_policy' => $genderPolicy,
            'capacity' => 10, 'isolation_capable' => true, 'is_active' => true,
        ]);
    }

    protected function makeRoom(?IpdWard $ward = null, string $genderPolicy = 'any'): IpdRoom
    {
        $ward ??= $this->makeWard();

        return IpdRoom::create([
            'organization_id' => $this->organization->id, 'branch_id' => null, 'ward_id' => $ward->id,
            'room_number' => (string) random_int(100, 999), 'room_type' => 'general',
            'capacity' => 2, 'gender_policy' => $genderPolicy, 'isolation_capable' => false, 'is_active' => true,
        ]);
    }

    protected function makeBed(?IpdRoom $room = null, string $genderType = 'any', bool $isolationCapable = false): IpdBed
    {
        $room ??= $this->makeRoom();
        $bedType = IpdBedType::create([
            'organization_id' => $this->organization->id, 'branch_id' => null,
            'code' => 'GEN-'.uniqid(), 'name' => 'General', 'is_active' => true,
        ]);

        return IpdBed::create([
            'organization_id' => $this->organization->id, 'branch_id' => null, 'room_id' => $room->id,
            'bed_type_id' => $bedType->id, 'bed_code' => 'BED-'.uniqid(),
            'gender_type' => $genderType, 'status' => IpdBed::STATUS_AVAILABLE,
            'isolation_capable' => $isolationCapable, 'is_active' => true,
        ]);
    }

    protected function makeProvider(string $role = 'attending'): Provider
    {
        return Provider::create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id,
            'provider_code' => 'PRV-'.uniqid(), 'name' => 'Dr. '.ucfirst($role),
            'provider_type' => 'doctor', 'status' => 'active',
        ]);
    }
}
