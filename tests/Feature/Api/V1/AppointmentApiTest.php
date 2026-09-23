<?php

namespace Tests\Feature\Api\V1;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AppointmentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);

        foreach (['appointments.view', 'appointments.create', 'appointments.update', 'appointments.confirm', 'appointments.checkin', 'appointments.cancel', 'appointments.reschedule', 'appointments.no_show'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $token = $this->user->createToken('test-token')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token);
        $this->withHeader('X-Company-Id', (string) $this->company->id);
        $this->withHeader('X-Branch-Id', (string) $branch->id);
    }

    public function test_user_can_list_appointments(): void
    {
        Appointment::factory()->count(2)->create(['company_id' => $this->company->id]);

        $response = $this->get('/api/v1/appointments');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_user_can_create_an_appointment(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $doctor = User::factory()->create();

        $response = $this->post('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '10:00',
            'type' => 'scheduled',
            'source' => 'api',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('appointments', ['patient_id' => $patient->id, 'status' => 'scheduled']);
    }

    public function test_user_can_confirm_and_check_in_an_appointment(): void
    {
        $appointment = Appointment::factory()->create(['company_id' => $this->company->id, 'status' => 'scheduled']);

        $this->post("/api/v1/appointments/{$appointment->id}/confirm")->assertOk();
        $this->assertSame('confirmed', $appointment->fresh()->status);

        $this->post("/api/v1/appointments/{$appointment->id}/check-in")->assertOk();
        $this->assertSame('checked_in', $appointment->fresh()->status);
    }

    public function test_availability_endpoint_returns_slots(): void
    {
        $provider = Provider::factory()->create(['company_id' => $this->company->id]);

        $response = $this->get("/api/v1/appointments/availability?provider_id={$provider->id}&date=".now()->addDay()->toDateString());

        $response->assertOk();
    }

    public function test_providers_endpoint_lists_active_providers(): void
    {
        Provider::factory()->create(['company_id' => $this->company->id, 'status' => 'active']);
        Provider::factory()->create(['company_id' => $this->company->id, 'status' => 'inactive']);

        $response = $this->get('/api/v1/providers');

        $response->assertOk()->assertJsonCount(1, 'data');
    }
}
