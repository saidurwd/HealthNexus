<?php

namespace Tests\Feature\Appointments;

use App\Models\AppointmentRoom;
use App\Models\AppointmentType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\DoctorSchedule;
use App\Models\HospitalHoliday;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Every new admin page added for the Phase 2 rebuild (providers, holidays, provider
 * unavailability, rooms, appointment types, schedule show/edit, calendars) is hit once as a
 * super_admin (which bypasses all permission checks via Gate::before) to catch missing views,
 * undefined variables, or broken relations that a permission-focused test wouldn't reach.
 */
class AppointmentAdminPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create();
        $this->user->assignRole('super_admin');
        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);

        $this->actingAs($this->user);
        session()->put('tenant_company_id', $this->company->id);
        session()->put('tenant_branch_id', $this->branch->id);
        app(\App\Services\TenantContextResolver::class)->setCompanyId($this->company->id);
        app(\App\Services\TenantContextResolver::class)->setBranchId($this->branch->id);
    }

    public function test_provider_pages_render(): void
    {
        $provider = Provider::factory()->create(['company_id' => $this->company->id]);

        $this->get('/admin/providers')->assertOk();
        $this->get('/admin/providers/create')->assertOk();
        $this->get("/admin/providers/{$provider->id}")->assertOk();
        $this->get("/admin/providers/{$provider->id}/edit")->assertOk();
    }

    public function test_holiday_pages_render(): void
    {
        HospitalHoliday::create(['company_id' => $this->company->id, 'name' => 'New Year', 'date' => '2026-01-01']);

        $this->get('/admin/holidays')->assertOk();
        $this->get('/admin/holidays/create')->assertOk();
    }

    public function test_provider_unavailability_page_renders(): void
    {
        $this->get('/admin/provider-unavailability')->assertOk();
    }

    public function test_appointment_room_pages_render(): void
    {
        $room = AppointmentRoom::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'name' => 'Room 1', 'room_type' => 'consultation',
        ]);

        $this->get('/admin/appointment-rooms')->assertOk();
        $this->get('/admin/appointment-rooms/create')->assertOk();
        $this->get("/admin/appointment-rooms/{$room->id}/edit")->assertOk();
    }

    public function test_appointment_type_pages_render(): void
    {
        $type = AppointmentType::create(['code' => 'consult', 'name' => 'Consultation']);

        $this->get('/admin/appointment-types')->assertOk();
        $this->get('/admin/appointment-types/create')->assertOk();
        $this->get("/admin/appointment-types/{$type->id}/edit")->assertOk();
    }

    public function test_schedule_show_and_edit_pages_render(): void
    {
        $doctor = User::factory()->create();
        $schedule = DoctorSchedule::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'doctor_id' => $doctor->id,
        ]);

        $this->get("/admin/schedules/{$schedule->id}")->assertOk();
        $this->get("/admin/schedules/{$schedule->id}/edit")->assertOk();
    }

    public function test_calendar_pages_render(): void
    {
        $this->get('/admin/appointments-calendar')->assertOk();
        $this->get('/admin/appointments-calendar/provider')->assertOk();
    }

    public function test_appointment_print_page_renders(): void
    {
        $patient = \App\Models\Patient::factory()->create(['company_id' => $this->company->id]);
        $doctor = User::factory()->create();

        $appointment = \App\Models\Appointment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_no' => 'APT-PRINT-1',
            'appointment_date' => today(),
            'appointment_time' => '10:00',
            'type' => 'scheduled',
            'source' => 'online',
        ]);

        $this->get("/admin/appointments/{$appointment->id}/print")->assertOk();
    }
}
