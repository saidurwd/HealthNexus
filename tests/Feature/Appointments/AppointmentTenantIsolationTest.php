<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * AppointmentPolicy::update()/delete()/confirm()/checkIn()/cancel()/reschedule()/markNoShow()
 * previously checked only the Spatie permission string, with no check that the target
 * appointment belongs to a company the user is actually assigned to — a user with
 * appointments.update could confirm/reschedule/cancel/delete another company's appointment if
 * they could reach the route. These tests guard against that regression (mirrors
 * PatientTenantIsolationTest for the same class of bug already fixed in Phase 1).
 */
class AppointmentTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $ownCompany;

    private Company $otherCompany;

    private Appointment $otherCompanyAppointment;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ownCompany = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $this->ownCompany->id]);
        $this->otherCompany = Company::factory()->create();

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->ownCompany->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($branch->id, ['access_level' => 'manager', 'company_id' => $this->ownCompany->id]);

        foreach ([
            'appointments.view', 'appointments.update', 'appointments.delete', 'appointments.confirm',
            'appointments.checkin', 'appointments.cancel', 'appointments.reschedule', 'appointments.no_show',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->actingAs($this->user);
        session()->put('tenant_company_id', $this->ownCompany->id);
        app(TenantContextResolver::class)->setCompanyId($this->ownCompany->id);

        $this->otherCompanyAppointment = Appointment::factory()->create(['company_id' => $this->otherCompany->id, 'status' => 'scheduled']);
    }

    public function test_cannot_confirm_another_companys_appointment(): void
    {
        $this->post("/admin/appointments/{$this->otherCompanyAppointment->id}/confirm")->assertForbidden();
        $this->assertSame('scheduled', $this->otherCompanyAppointment->fresh()->status);
    }

    public function test_cannot_check_in_another_companys_appointment(): void
    {
        $this->otherCompanyAppointment->update(['status' => 'confirmed']);

        $this->post("/admin/appointments/{$this->otherCompanyAppointment->id}/check-in")->assertForbidden();
        $this->assertSame('confirmed', $this->otherCompanyAppointment->fresh()->status);
    }

    public function test_cannot_cancel_another_companys_appointment(): void
    {
        $this->post("/admin/appointments/{$this->otherCompanyAppointment->id}/cancel", [
            'cancellation_reason' => 'Trying to interfere',
        ])->assertForbidden();

        $this->assertSame('scheduled', $this->otherCompanyAppointment->fresh()->status);
    }

    public function test_cannot_reschedule_another_companys_appointment(): void
    {
        $this->post("/admin/appointments/{$this->otherCompanyAppointment->id}/reschedule", [
            'appointment_date' => now()->addDays(5)->toDateString(),
            'appointment_time' => '11:00',
        ])->assertForbidden();
    }

    public function test_cannot_mark_another_companys_appointment_no_show(): void
    {
        $this->post("/admin/appointments/{$this->otherCompanyAppointment->id}/no-show")->assertForbidden();
        $this->assertSame('scheduled', $this->otherCompanyAppointment->fresh()->status);
    }

    public function test_cannot_update_another_companys_appointment(): void
    {
        $this->put("/admin/appointments/{$this->otherCompanyAppointment->id}", [
            'reason' => 'Hacked reason',
        ])->assertForbidden();
    }

    public function test_cannot_delete_another_companys_appointment(): void
    {
        $this->delete("/admin/appointments/{$this->otherCompanyAppointment->id}")->assertForbidden();
        $this->assertDatabaseHas('appointments', ['id' => $this->otherCompanyAppointment->id, 'deleted_at' => null]);
    }

    public function test_own_company_appointment_can_still_be_confirmed(): void
    {
        $appointment = Appointment::factory()->create(['company_id' => $this->ownCompany->id, 'status' => 'scheduled']);

        $this->post("/admin/appointments/{$appointment->id}/confirm")->assertRedirect();
        $this->assertSame('confirmed', $appointment->fresh()->status);
    }
}
