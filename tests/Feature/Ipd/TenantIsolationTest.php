<?php

namespace Tests\Feature\Ipd;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Patient;
use App\Services\TenantContextResolver;
use Spatie\Permission\Models\Permission;

/**
 * The spec's explicitly "most important" security test (spec §75/§86): a Hospital A user cannot
 * view or act on Hospital B's admissions/beds, via direct model-policy authorization and via the
 * web UI.
 */
class TenantIsolationTest extends IpdTestCase
{
    public function test_a_user_cannot_view_another_companys_admission(): void
    {
        $bed = $this->makeBed();
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $encounter = Encounter::factory()->create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
        ]);
        $admission = IpdAdmission::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-TEST-'.uniqid(), 'patient_id' => $patient->id,
            'encounter_id' => $encounter->id, 'admitted_at' => now(), 'status' => 'admitted',
        ]);

        $otherCompany = Company::factory()->create();
        $otherBranch = Branch::factory()->create(['company_id' => $otherCompany->id]);
        $otherUser = \App\Models\User::factory()->create();
        $otherUser->companies()->attach($otherCompany->id, ['access_level' => 'admin']);
        $otherUser->branches()->attach($otherBranch->id, ['access_level' => 'manager', 'company_id' => $otherCompany->id]);
        Permission::firstOrCreate(['name' => 'ipd.admission.view', 'guard_name' => 'web']);
        $otherUser->givePermissionTo('ipd.admission.view');

        $this->actingAs($otherUser);
        session()->put('tenant_company_id', $otherCompany->id);
        app(TenantContextResolver::class)->setCompanyId($otherCompany->id);

        $this->assertFalse($otherUser->can('view', $admission));

        $response = $this->get('/admin/ipd/admissions/'.$admission->id);
        $response->assertForbidden();
    }

    public function test_admission_listing_is_scoped_to_the_current_company(): void
    {
        $bed = $this->makeBed();
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $encounter = Encounter::factory()->create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
        ]);
        IpdAdmission::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-TEST-'.uniqid(), 'patient_id' => $patient->id,
            'encounter_id' => $encounter->id, 'admitted_at' => now(), 'status' => 'admitted',
        ]);

        $otherCompany = Company::factory()->create();

        $this->assertSame(1, IpdAdmission::forTenant($this->company->id)->count());
        $this->assertSame(0, IpdAdmission::forTenant($otherCompany->id)->count());
    }

    public function test_a_user_cannot_allocate_a_bed_belonging_to_another_company(): void
    {
        $bed = $this->makeBed();

        $otherCompany = Company::factory()->create();
        $otherBranch = Branch::factory()->create(['company_id' => $otherCompany->id]);
        $otherUser = \App\Models\User::factory()->create();
        $otherUser->companies()->attach($otherCompany->id, ['access_level' => 'admin']);
        $otherUser->branches()->attach($otherBranch->id, ['access_level' => 'manager', 'company_id' => $otherCompany->id]);
        Permission::firstOrCreate(['name' => 'ipd.bed.allocate', 'guard_name' => 'web']);
        $otherUser->givePermissionTo('ipd.bed.allocate');

        $this->assertFalse($otherUser->can('allocate', $bed));
    }
}
