<?php

namespace Tests\Feature\Clinical;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use App\Services\BreakGlassService;
use App\Services\Clinical\EncounterClinicalService;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Guards the Phase 3 fixes against regression: cross-hospital access to another company's
 * encounter, writes to a locked/completed encounter bypassing the amendment workflow, and
 * break-glass access that isn't actually logged as a security event.
 */
class EncounterSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $ownCompany;

    private Branch $ownBranch;

    private Company $otherCompany;

    private Encounter $otherCompanyEncounter;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ownCompany = Company::factory()->create();
        $this->ownBranch = Branch::factory()->create(['company_id' => $this->ownCompany->id]);
        $this->otherCompany = Company::factory()->create();
        $otherBranch = Branch::factory()->create(['company_id' => $this->otherCompany->id]);
        $otherPatient = Patient::factory()->create(['company_id' => $this->otherCompany->id]);

        $this->otherCompanyEncounter = Encounter::factory()->create([
            'company_id' => $this->otherCompany->id,
            'branch_id' => $otherBranch->id,
            'patient_id' => $otherPatient->id,
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->ownCompany->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->ownBranch->id, ['access_level' => 'manager', 'company_id' => $this->ownCompany->id]);

        foreach ([
            'encounters.view', 'encounters.update', 'encounters.delete',
            'clinical.vitals.create', 'clinical.diagnosis.create', 'encounter.amend', 'clinical.break_glass',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->actingAs($this->user);
        session()->put('tenant_company_id', $this->ownCompany->id);
        app(TenantContextResolver::class)->setCompanyId($this->ownCompany->id);
    }

    public function test_user_cannot_view_another_companys_encounter(): void
    {
        $response = $this->get('/admin/encounters/'.$this->otherCompanyEncounter->id);

        $response->assertStatus(403);
    }

    public function test_user_cannot_update_another_companys_encounter(): void
    {
        $response = $this->put('/admin/encounters/'.$this->otherCompanyEncounter->id, [
            'status' => 'waiting',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_another_companys_encounter(): void
    {
        $response = $this->delete('/admin/encounters/'.$this->otherCompanyEncounter->id);

        $response->assertStatus(403);
        $this->assertDatabaseHas('encounters', [
            'id' => $this->otherCompanyEncounter->id,
            'deleted_at' => null,
        ]);
    }

    public function test_locked_encounter_rejects_new_vitals_via_policy(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->ownCompany->id]);
        $encounter = Encounter::factory()->create([
            'company_id' => $this->ownCompany->id,
            'branch_id' => $this->ownBranch->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
            'locked_at' => now(),
        ]);

        $response = $this->post("/admin/encounters/{$encounter->id}/vitals", [
            'systolic' => 120,
            'diastolic' => 80,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('vital_signs', ['encounter_id' => $encounter->id]);
    }

    public function test_locked_encounter_rejects_new_diagnosis_via_policy(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->ownCompany->id]);
        $encounter = Encounter::factory()->create([
            'company_id' => $this->ownCompany->id,
            'branch_id' => $this->ownBranch->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
            'locked_at' => now(),
        ]);

        $response = $this->post("/admin/encounters/{$encounter->id}/diagnoses", [
            'description' => 'Should be blocked',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('diagnoses', ['encounter_id' => $encounter->id]);
    }

    public function test_amendment_workflow_leaves_locked_encounter_untouched(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->ownCompany->id]);
        $encounter = Encounter::factory()->create([
            'company_id' => $this->ownCompany->id,
            'branch_id' => $this->ownBranch->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
            'locked_at' => now(),
        ]);

        $lockedAtBefore = $encounter->locked_at;

        $amendment = app(EncounterClinicalService::class)->createAmendment($encounter, [
            'amendment_type' => 'correction',
            'reason' => 'Wrong diagnosis code entered',
            'content' => 'ICD-10 code corrected from I10 to I11.',
        ], $this->user);

        $encounter->refresh();

        $this->assertSame('completed', $encounter->status);
        $this->assertEquals($lockedAtBefore->toDateTimeString(), $encounter->locked_at->toDateTimeString());
        $this->assertDatabaseHas('encounter_amendments', [
            'id' => $amendment->id,
            'encounter_id' => $encounter->id,
            'approved_at' => null,
        ]);
    }

    public function test_amendment_cannot_be_approved_by_its_own_creator(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->ownCompany->id]);
        $encounter = Encounter::factory()->create([
            'company_id' => $this->ownCompany->id,
            'branch_id' => $this->ownBranch->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
            'locked_at' => now(),
        ]);

        $service = app(EncounterClinicalService::class);

        $amendment = $service->createAmendment($encounter, [
            'amendment_type' => 'correction',
            'reason' => 'Self-approval attempt',
            'content' => 'Should be rejected.',
        ], $this->user);

        $this->expectException(ValidationException::class);

        $service->approveAmendment($amendment, $this->user);
    }

    public function test_break_glass_access_within_own_company_is_logged_as_a_security_event(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->ownCompany->id]);
        $encounter = Encounter::factory()->create([
            'company_id' => $this->ownCompany->id,
            'branch_id' => $this->ownBranch->id,
            'patient_id' => $patient->id,
        ]);

        $response = $this->post("/admin/encounters/{$encounter->id}/break-glass", [
            'reason' => 'Emergency access required for restricted record.',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('break_glass_accesses', [
            'user_id' => $this->user->id,
            'encounter_id' => $encounter->id,
        ]);

        $this->assertDatabaseHas('security_events', [
            'user_id' => $this->user->id,
            'event' => 'BreakGlassAccess',
        ]);
    }

    public function test_break_glass_cannot_be_requested_for_another_companys_encounter(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(BreakGlassService::class)->requestAccess(
            $this->otherCompanyEncounter,
            'Trying to reach across tenants',
            $this->user,
        );
    }
}
