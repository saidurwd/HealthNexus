<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\AppointmentToken;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QueueTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'doctor', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);

        $this->user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);

        foreach (['manage organizations', 'appointments.queue', 'appointments.checkin', 'appointments.update'] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->actingAs($this->user);

        session()->put('tenant_organization_id', $this->organization->id);
        session()->put('tenant_branch_id', $this->branch->id);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
        app(TenantContextResolver::class)->setBranchId($this->branch->id);
    }

    public function test_user_can_view_queue(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $doctor = User::factory()->create();

        $appointment = Appointment::create([
            'organization_id' => $this->organization->id,
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
            'organization_id' => $this->organization->id,
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
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $doctor = User::factory()->create();

        $appointment = Appointment::create([
            'organization_id' => $this->organization->id,
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
            'organization_id' => $this->organization->id,
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
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $doctor = User::factory()->create();

        $appointment = Appointment::create([
            'organization_id' => $this->organization->id,
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
            'organization_id' => $this->organization->id,
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
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $doctor = User::factory()->create();

        $appointment = Appointment::create([
            'organization_id' => $this->organization->id,
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
            'organization_id' => $this->organization->id,
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
