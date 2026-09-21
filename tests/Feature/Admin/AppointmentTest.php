<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\AppointmentToken;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Diagnosis;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Branch $branch;

    private Patient $patient;

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
        Permission::create(['name' => 'appointments.delete', 'guard_name' => 'web']);
        $this->user->givePermissionTo('manage companies');
        $this->user->givePermissionTo('appointments.create');
        $this->user->givePermissionTo('appointments.update');
        $this->user->givePermissionTo('appointments.delete');

        $this->actingAs($this->user);

        session()->put('tenant_company_id', $this->company->id);
        session()->put('tenant_branch_id', $this->branch->id);
        app(\App\Services\TenantContextResolver::class)->setCompanyId($this->company->id);
        app(\App\Services\TenantContextResolver::class)->setBranchId($this->branch->id);

        $this->patient = Patient::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    public function test_user_can_view_appointments_index(): void
    {
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
        ]);

        $response = $this->get('/admin/appointments');

        $response->assertStatus(200);
        $response->assertViewHas('appointments');
    }

    public function test_user_can_create_appointment(): void
    {
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $response = $this->post('/admin/appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => '2025-01-15',
            'appointment_time' => '10:00',
            'type' => 'scheduled',
            'source' => 'online',
            'reason' => 'Routine checkup',
        ]);

        $response->assertRedirect('/admin/appointments');
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $doctor->id,
            'reason' => 'Routine checkup',
        ]);
        $this->assertDatabaseHas('appointment_tokens', [
            'appointment_id' => Appointment::first()->id,
        ]);
    }

    public function test_user_can_view_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
        ]);

        $response = $this->get('/admin/appointments/'.$appointment->id);

        $response->assertStatus(200);
        $response->assertViewHas('appointment');
    }

    public function test_user_can_update_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
        ]);

        $response = $this->put('/admin/appointments/'.$appointment->id, [
            'doctor_id' => $this->user->id,
            'appointment_date' => '2025-01-20',
            'appointment_time' => '14:00',
            'status' => 'confirmed',
            'notes' => 'Updated notes',
        ]);

        $response->assertRedirect('/admin/appointments');
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_user_can_delete_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
        ]);

        $response = $this->delete('/admin/appointments/'.$appointment->id);

        $response->assertRedirect('/admin/appointments');
        $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);
    }
}
