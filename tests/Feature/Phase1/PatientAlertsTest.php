<?php

namespace Tests\Feature\Phase1;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\PatientAlert;
use App\Models\User;
use App\Services\Patients\PatientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PatientAlertsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($branch->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);

        Permission::create(['name' => 'manage companies', 'guard_name' => 'web']);
        Permission::create(['name' => 'patients.update', 'guard_name' => 'web']);
        $this->user->givePermissionTo('manage companies');
        $this->user->givePermissionTo('patients.update');

        $this->patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);
        session()->put('tenant_company_id', $this->company->id);
    }

    private function alertPayload(): array
    {
        return [
            'alert_type' => 'allergy',
            'title' => 'Penicillin Allergy',
            'description' => 'Patient reports severe reaction to penicillin.',
            'severity' => 'critical',
            'start_at' => '2026-09-01',
            'expires_at' => '2027-09-01',
        ];
    }

    public function test_admin_can_view_alerts_page(): void
    {
        $this->get('/admin/patients/'.$this->patient->id.'/alerts')->assertOk();
    }

    public function test_admin_can_add_alert_and_it_is_audited(): void
    {
        $this->post('/admin/patients/'.$this->patient->id.'/alerts', $this->alertPayload())
            ->assertRedirect('/admin/patients/'.$this->patient->id.'/alerts');

        $this->assertDatabaseHas('patient_alerts', [
            'patient_id' => $this->patient->id,
            'title' => 'Penicillin Allergy',
            'severity' => 'critical',
            'company_id' => $this->company->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'CREATE',
            'model_type' => PatientAlert::class,
        ]);
    }

    public function test_alert_appears_in_list(): void
    {
        app(PatientService::class)->addAlert($this->patient, $this->alertPayload());

        $this->get('/admin/patients/'.$this->patient->id.'/alerts')
            ->assertOk()
            ->assertSee('Penicillin Allergy');
    }

    public function test_resolve_alert_sets_resolved_at(): void
    {
        $alert = PatientAlert::factory()->create([
            'patient_id' => $this->patient->id,
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $this->put('/admin/patients/'.$this->patient->id.'/alerts/'.$alert->id.'/resolve', [
            'resolution_note' => 'Confirmed via chart review.',
        ])->assertRedirect();

        $alert->refresh();
        $this->assertSame('resolved', $alert->status);
        $this->assertNotNull($alert->resolved_at);
        $this->assertEquals($this->user->id, $alert->resolved_by);
    }

    public function test_admin_can_delete_alert(): void
    {
        $alert = PatientAlert::factory()->create([
            'patient_id' => $this->patient->id,
            'company_id' => $this->company->id,
        ]);

        $this->delete('/admin/patients/'.$this->patient->id.'/alerts/'.$alert->id)
            ->assertRedirect();

        $this->assertSoftDeleted($alert);
    }
}
