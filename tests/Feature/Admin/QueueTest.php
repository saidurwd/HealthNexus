<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\AppointmentToken;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QueueTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'doctor', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);

        foreach (['manage companies', 'appointments.queue', 'appointments.checkin', 'appointments.update'] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->actingAs($this->user);

        session()->put('tenant_company_id', $this->company->id);
        session()->put('tenant_branch_id', $this->branch->id);
        app(\App\Services\TenantContextResolver::class)->setCompanyId($this->company->id);
        app(\App\Services\TenantContextResolver::class)->setBranchId($this->branch->id);
    }

    public function test_user_can_view_queue(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $doctor = User::factory()->create();

        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_no' => 'APT-001',
            'appointment_date' => today(),
            'appointment_time' => '10:00',
            'type' => 'scheduled',
            'source' => 'online',
            'reason' => 'Checkup',
        ]);

        AppointmentToken::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'appointment_id' => $appointment->id,
            'token_number' => 'T-001',
            'status' => 'waiting',
            'generated_at' => now(),
        ]);

        $response = $this->get('/admin/queue');

        $response->assertStatus(200);
        $response->assertViewHas('queue');
    }

    public function test_user_can_call_next_token(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $doctor = User::factory()->create();

        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_no' => 'APT-001',
            'appointment_date' => today(),
            'appointment_time' => '10:00',
            'type' => 'scheduled',
            'source' => 'online',
            'reason' => 'Checkup',
        ]);

        $token = AppointmentToken::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'appointment_id' => $appointment->id,
            'token_number' => 'T-001',
            'status' => 'waiting',
            'generated_at' => now(),
        ]);

        $response = $this->postJson('/admin/queue/call-next');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('appointment_tokens', [
            'id' => $token->id,
            'status' => 'called',
        ]);
    }

    public function test_user_can_start_token(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $doctor = User::factory()->create();

        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_no' => 'APT-001',
            'appointment_date' => today(),
            'appointment_time' => '10:00',
            'type' => 'scheduled',
            'source' => 'online',
            'reason' => 'Checkup',
            'status' => 'checked_in',
        ]);

        $token = AppointmentToken::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'appointment_id' => $appointment->id,
            'token_number' => 'T-001',
            'status' => 'called',
            'called_at' => now(),
        ]);

        $response = $this->postJson('/admin/queue/'.$token->id.'/start');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('appointment_tokens', [
            'id' => $token->id,
            'status' => 'in_progress',
        ]);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_user_can_complete_token(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $doctor = User::factory()->create();

        $appointment = Appointment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_no' => 'APT-001',
            'appointment_date' => today(),
            'appointment_time' => '10:00',
            'type' => 'scheduled',
            'source' => 'online',
            'reason' => 'Checkup',
            'status' => 'in_progress',
        ]);

        $token = AppointmentToken::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'appointment_id' => $appointment->id,
            'token_number' => 'T-001',
            'status' => 'in_progress',
        ]);

        $response = $this->postJson('/admin/queue/'.$token->id.'/complete');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('appointment_tokens', [
            'id' => $token->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'completed',
        ]);
    }
}
