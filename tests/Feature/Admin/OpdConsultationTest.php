<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Diagnosis;
use App\Models\InvestigationOrder;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Models\VitalSign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpdConsultationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Branch $branch;

    private Patient $patient;

    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'doctor', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);
        Permission::create(['name' => 'manage companies', 'guard_name' => 'web']);
        Permission::create(['name' => 'appointments.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'appointments.update', 'guard_name' => 'web']);
        $this->user->givePermissionTo('manage companies');
        $this->user->givePermissionTo('appointments.create');
        $this->user->givePermissionTo('appointments.update');

        $this->actingAs($this->user);

        session()->put('tenant_company_id', $this->company->id);
        session()->put('tenant_branch_id', $this->branch->id);
        app(\App\Services\TenantContextResolver::class)->setCompanyId($this->company->id);
        app(\App\Services\TenantContextResolver::class)->setBranchId($this->branch->id);

        $this->patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $this->appointment = Appointment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->user->id,
            'appointment_no' => 'APT-001',
            'appointment_date' => today(),
            'appointment_time' => '10:00',
            'type' => 'scheduled',
            'source' => 'online',
            'reason' => 'Routine checkup',
        ]);

        \App\Models\AppointmentToken::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'appointment_id' => $this->appointment->id,
            'token_number' => 'T-001',
            'status' => 'waiting',
            'generated_at' => now(),
        ]);
    }

    public function test_user_can_view_consultation(): void
    {
        $response = $this->get('/admin/appointments/'.$this->appointment->id.'/consultation');

        $response->assertStatus(200);
        $response->assertViewHas('appointment');
    }

    public function test_user_can_record_vital_signs(): void
    {
        $response = $this->post('/admin/opd/vital-signs', [
            'appointment_id' => $this->appointment->id,
            'temperature' => 36.5,
            'systolic' => 120,
            'diastolic' => 80,
            'pulse_rate' => 75,
            'respiratory_rate' => 16,
            'height' => 170.5,
            'weight' => 70.2,
            'oxygen_saturation' => 98,
            'notes' => 'Normal readings',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vital_signs', [
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->patient->id,
            'systolic' => 120,
            'diastolic' => 80,
        ]);
    }

    public function test_user_can_add_diagnosis(): void
    {
        $response = $this->post('/admin/opd/diagnoses', [
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->patient->id,
            'code_type' => 'ICD-10',
            'code' => 'K59.0',
            'description' => 'Constipation',
            'status' => 'confirmed',
            'notes' => 'Chronic constipation',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('diagnoses', [
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->patient->id,
            'description' => 'Constipation',
            'status' => 'confirmed',
        ]);
    }

    public function test_user_can_add_investigation_order(): void
    {
        $response = $this->post('/admin/opd/investigation-orders', [
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->patient->id,
            'test_name' => 'Complete Blood Count',
            'category' => 'Laboratory',
            'clinical_notes' => 'Check for anemia',
            'priority' => 'routine',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('investigation_orders', [
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->patient->id,
            'test_name' => 'Complete Blood Count',
            'priority' => 'routine',
        ]);
    }

    public function test_user_can_create_prescription(): void
    {
        $response = $this->post('/admin/opd/prescriptions', [
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->patient->id,
            'clinical_notes' => 'Patient has fever',
            'advice' => 'Rest and hydration',
            'items' => [
                [
                    'medicine_name' => 'Paracetamol',
                    'dosage_form' => 'tablet',
                    'strength' => '500mg',
                    'frequency' => 'BID',
                    'duration' => '5 days',
                    'quantity' => 10,
                    'instructions' => 'After meals',
                ],
                [
                    'medicine_name' => 'Cough Syrup',
                    'dosage_form' => 'liquid',
                    'strength' => '15mg/5ml',
                    'frequency' => 'TID',
                    'duration' => '7 days',
                    'quantity' => 1,
                    'instructions' => 'Before sleeping',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('prescriptions', [
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->patient->id,
            'clinical_notes' => 'Patient has fever',
        ]);
        $this->assertDatabaseHas('prescription_items', [
            'medicine_name' => 'Paracetamol',
            'frequency' => 'BID',
        ]);
        $this->assertDatabaseHas('prescription_items', [
            'medicine_name' => 'Cough Syrup',
            'frequency' => 'TID',
        ]);
    }

    public function test_user_can_mark_appointment_in_progress(): void
    {
        $response = $this->post('/admin/appointments/'.$this->appointment->id.'/status/in-progress');

        $response->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'id' => $this->appointment->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_user_can_mark_appointment_completed(): void
    {
        $this->appointment->update(['status' => 'in_progress']);

        $response = $this->post('/admin/appointments/'.$this->appointment->id.'/status/completed');

        $response->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'id' => $this->appointment->id,
            'status' => 'completed',
        ]);
    }

    public function test_vital_sign_validation_requires_appointment(): void
    {
        $response = $this->post('/admin/opd/vital-signs', [
            'temperature' => 36.5,
        ]);

        $response->assertSessionHasErrors('appointment_id');
    }

    public function test_diagnosis_validation_requires_description(): void
    {
        $response = $this->post('/admin/opd/diagnoses', [
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->patient->id,
        ]);

        $response->assertSessionHasErrors('description');
    }

    public function test_investigation_order_validation_requires_test_name(): void
    {
        $response = $this->post('/admin/opd/investigation-orders', [
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->patient->id,
        ]);

        $response->assertSessionHasErrors('test_name');
    }

    public function test_prescription_requires_at_least_one_item(): void
    {
        $response = $this->post('/admin/opd/prescriptions', [
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->patient->id,
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_user_can_check_in_appointment(): void
    {
        $response = $this->post('/admin/appointments/'.$this->appointment->id.'/check-in');

        $response->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'id' => $this->appointment->id,
            'status' => 'checked_in',
        ]);
        $this->assertDatabaseHas('appointment_tokens', [
            'appointment_id' => $this->appointment->id,
            'status' => 'checked_in',
        ]);
    }

    public function test_user_can_create_follow_up_appointment(): void
    {
        $response = $this->post('/admin/appointments/'.$this->appointment->id.'/follow-up', [
            'appointment_date' => '2025-02-01',
            'appointment_time' => '10:00',
            'reason' => 'Follow-up on treatment',
            'notes' => 'Monitor progress',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->user->id,
            'type' => 'followup',
            'source' => 'followup',
            'reason' => 'Follow-up on treatment',
        ]);
        $this->assertDatabaseHas('appointment_tokens', [
            'appointment_id' => Appointment::where('type', 'followup')->first()->id,
        ]);
    }
}
