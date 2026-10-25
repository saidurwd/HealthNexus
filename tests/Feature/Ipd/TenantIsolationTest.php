<?php

namespace Tests\Feature\Ipd;

use App\Models\Branch;
use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\TenantContextResolver;
use Spatie\Permission\Models\Permission;

/**
 * The spec's explicitly "most important" security test (spec §75/§86): a Hospital A user cannot
 * view or act on Hospital B's admissions/beds, via direct model-policy authorization and via the
 * web UI.
 */
class TenantIsolationTest extends IpdTestCase
{
    public function test_a_user_cannot_view_another_organizations_admission(): void
    {
        $bed = $this->makeBed();
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $encounter = Encounter::factory()->create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
        ]);
        $admission = IpdAdmission::create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-TEST-'.uniqid(), 'patient_id' => $patient->id,
            'encounter_id' => $encounter->id, 'admitted_at' => now(), 'status' => 'admitted',
        ]);

        $otherOrganization = Organization::factory()->create();
        $otherBranch = Branch::factory()->create(['organization_id' => $otherOrganization->id]);
        $otherUser = User::factory()->create();
        $otherUser->organizations()->attach($otherOrganization->id, ['access_level' => 'admin']);
        $otherUser->branches()->attach($otherBranch->id, ['access_level' => 'manager', 'organization_id' => $otherOrganization->id]);
        Permission::firstOrCreate(['name' => 'ipd.admission.view', 'guard_name' => 'web']);
        $otherUser->givePermissionTo('ipd.admission.view');

        $this->actingAs($otherUser);
        session()->put('tenant_organization_id', $otherOrganization->id);
        app(TenantContextResolver::class)->setOrganizationId($otherOrganization->id);

        $this->assertFalse($otherUser->can('view', $admission));

        $response = $this->get('/admin/ipd/admissions/'.$admission->public_id);
        $response->assertForbidden();
    }

    public function test_admission_listing_is_scoped_to_the_current_organization(): void
    {
        $bed = $this->makeBed();
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $encounter = Encounter::factory()->create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
        ]);
        IpdAdmission::create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-TEST-'.uniqid(), 'patient_id' => $patient->id,
            'encounter_id' => $encounter->id, 'admitted_at' => now(), 'status' => 'admitted',
        ]);

        $otherOrganization = Organization::factory()->create();

        $this->assertSame(1, IpdAdmission::forTenant($this->organization->id)->count());
        $this->assertSame(0, IpdAdmission::forTenant($otherOrganization->id)->count());
    }

    public function test_a_user_cannot_allocate_a_bed_belonging_to_another_organization(): void
    {
        $bed = $this->makeBed();

        $otherOrganization = Organization::factory()->create();
        $otherBranch = Branch::factory()->create(['organization_id' => $otherOrganization->id]);
        $otherUser = User::factory()->create();
        $otherUser->organizations()->attach($otherOrganization->id, ['access_level' => 'admin']);
        $otherUser->branches()->attach($otherBranch->id, ['access_level' => 'manager', 'organization_id' => $otherOrganization->id]);
        Permission::firstOrCreate(['name' => 'ipd.bed.allocate', 'guard_name' => 'web']);
        $otherUser->givePermissionTo('ipd.bed.allocate');

        $this->assertFalse($otherUser->can('allocate', $bed));
    }
}
