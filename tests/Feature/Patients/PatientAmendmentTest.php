<?php

namespace Tests\Feature\Patients;

use App\Models\Company;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowApprover;
use App\Models\WorkflowStep;
use App\Services\Patients\PatientAmendmentService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PatientAmendmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Setting::create([
            'group' => 'patients',
            'key' => 'patients.amendment_sensitive_fields',
            'value' => json_encode(['date_of_birth', 'sex', 'national_identifier']),
            'type' => 'json',
        ]);
        app(SettingsService::class)->flush();
    }

    public function test_sensitive_fields_require_amendment_by_default(): void
    {
        $service = app(PatientAmendmentService::class);

        $this->assertTrue($service->requiresAmendment(['date_of_birth']));
        $this->assertTrue($service->requiresAmendment(['sex']));
        $this->assertTrue($service->requiresAmendment(['national_identifier']));
        $this->assertFalse($service->requiresAmendment(['phone']));
        // Name fields are deliberately excluded so routine typo fixes don't need approval.
        $this->assertFalse($service->requiresAmendment(['first_name']));
    }

    public function test_request_creates_a_pending_amendment_and_does_not_apply_immediately_when_a_workflow_exists(): void
    {
        $company = Company::factory()->create();
        $patient = Patient::factory()->create(['company_id' => $company->id, 'sex' => 'M']);
        $requester = User::factory()->create();
        $this->seedAmendmentWorkflow($company);

        $amendment = app(PatientAmendmentService::class)->request($patient, ['sex' => 'F'], 'Corrected from source ID', $requester);

        $this->assertSame('pending_approval', $amendment->status);
        $this->assertNotNull($amendment->workflow_instance_id);
        $this->assertSame('M', $patient->fresh()->sex);
    }

    public function test_approve_applies_the_proposed_changes_once_the_workflow_completes(): void
    {
        $company = Company::factory()->create();
        $patient = Patient::factory()->create(['company_id' => $company->id, 'sex' => 'M']);
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $approver->assignRole($this->hospitalAdminRole());

        $this->seedAmendmentWorkflow($company);

        $service = app(PatientAmendmentService::class);
        $amendment = $service->request($patient, ['sex' => 'F'], 'Corrected from source ID', $requester);

        $service->approve($amendment, $approver);

        $this->assertSame('applied', $amendment->fresh()->status);
        $this->assertSame('F', $patient->fresh()->sex);
        $this->assertDatabaseHas('patient_timeline_events', ['patient_id' => $patient->id, 'event_type' => 'AMENDMENT_APPLIED']);
    }

    public function test_reject_leaves_the_patient_record_unchanged(): void
    {
        $company = Company::factory()->create();
        $patient = Patient::factory()->create(['company_id' => $company->id, 'sex' => 'M']);
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $approver->assignRole($this->hospitalAdminRole());

        $this->seedAmendmentWorkflow($company);

        $service = app(PatientAmendmentService::class);
        $amendment = $service->request($patient, ['sex' => 'F'], 'Corrected from source ID', $requester);

        $service->reject($amendment, $approver, 'Not a valid correction');

        $this->assertSame('rejected', $amendment->fresh()->status);
        $this->assertSame('M', $patient->fresh()->sex);
    }

    public function test_request_applies_directly_when_no_workflow_is_configured_for_the_company(): void
    {
        $company = Company::factory()->create();
        $patient = Patient::factory()->create(['company_id' => $company->id, 'sex' => 'M']);
        $requester = User::factory()->create();

        $amendment = app(PatientAmendmentService::class)->request($patient, ['sex' => 'F'], 'No workflow configured', $requester);

        $this->assertSame('applied', $amendment->status);
        $this->assertSame('F', $patient->fresh()->sex);
    }

    private function hospitalAdminRole(): \Spatie\Permission\Models\Role
    {
        return \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'hospital_admin', 'guard_name' => 'web']);
    }

    private function seedAmendmentWorkflow(Company $company): Workflow
    {
        $workflow = Workflow::create([
            'company_id' => $company->id, 'code' => 'patient_amendment', 'name' => 'Patient Record Amendment', 'is_active' => true,
        ]);

        $step = WorkflowStep::create(['workflow_id' => $workflow->id, 'step_order' => 1, 'name' => 'Admin Approval', 'is_final' => true]);
        WorkflowApprover::create(['workflow_step_id' => $step->id, 'approver_type' => WorkflowApprover::TYPE_ROLE, 'role_name' => 'hospital_admin']);

        return $workflow;
    }
}
