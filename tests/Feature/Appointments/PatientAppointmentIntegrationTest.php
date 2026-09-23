<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Verifies the Phase 1 <-> Phase 2 boundary the spec is explicit about: appointment booking must
 * reference the existing Patient entity (never create a new one), and the Patient 360 profile
 * must surface appointment history and a working "Book Appointment" action.
 */
class PatientAppointmentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($branch->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);

        foreach (['patients.view', 'appointments.view', 'appointments.create'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->actingAs($this->user);
        session()->put('tenant_company_id', $this->company->id);
        session()->put('tenant_branch_id', $branch->id);
        app(TenantContextResolver::class)->setCompanyId($this->company->id);
        app(TenantContextResolver::class)->setBranchId($branch->id);

        $this->patient = Patient::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_book_appointment_button_preselects_the_patient(): void
    {
        $response = $this->get('/admin/appointments/create?patient_id='.$this->patient->id);

        $response->assertOk();
        $response->assertViewHas('selectedPatient', fn ($selected) => $selected->id === $this->patient->id);
    }

    public function test_booking_never_creates_a_new_patient_record(): void
    {
        $doctor = User::factory()->create();
        $countBefore = Patient::count();

        $this->post('/admin/appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '10:00',
            'type' => 'scheduled',
            'source' => 'online',
        ])->assertRedirect();

        $this->assertSame($countBefore, Patient::count());
        $this->assertDatabaseHas('appointments', ['patient_id' => $this->patient->id]);
    }

    public function test_invalid_patient_id_cannot_book_an_appointment(): void
    {
        $doctor = User::factory()->create();

        $this->post('/admin/appointments', [
            'patient_id' => 999999,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '10:00',
            'type' => 'scheduled',
            'source' => 'online',
        ])->assertSessionHasErrors('patient_id');
    }

    public function test_patient_profile_shows_appointment_history(): void
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'patient_id' => $this->patient->id,
            'appointment_no' => 'APT-HIST-1',
        ]);

        $response = $this->get("/admin/patients/{$this->patient->id}/appointments");

        $response->assertOk();
        $response->assertSee($appointment->appointment_no);
    }
}
