<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * AppointmentPolicy::update()/delete()/confirm()/checkIn()/cancel()/reschedule()/markNoShow()
 * previously checked only the Spatie permission string, with no check that the target
 * appointment belongs to an organization the user is actually assigned to — a user with
 * appointments.update could confirm/reschedule/cancel/delete another organization's appointment if
 * they could reach the route. These tests guard against that regression (mirrors
 * PatientTenantIsolationTest for the same class of bug already fixed in Phase 1).
 */
class AppointmentTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $ownOrganization;

    private Organization $otherOrganization;

    private Appointment $otherOrganizationAppointment;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ownOrganization = Organization::factory()->create();
        $branch = Branch::factory()->create(['organization_id' => $this->ownOrganization->id]);
        $this->otherOrganization = Organization::factory()->create();

        $this->user = User::factory()->create();
        $this->user->organizations()->attach($this->ownOrganization->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($branch->id, ['access_level' => 'manager', 'organization_id' => $this->ownOrganization->id]);

        foreach ([
            'appointments.view', 'appointments.update', 'appointments.delete', 'appointments.confirm',
            'appointments.checkin', 'appointments.cancel', 'appointments.reschedule', 'appointments.no_show',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->actingAs($this->user);
        session()->put('tenant_organization_id', $this->ownOrganization->id);
        app(TenantContextResolver::class)->setOrganizationId($this->ownOrganization->id);

        $this->otherOrganizationAppointment = Appointment::factory()->create(['organization_id' => $this->otherOrganization->id, 'status' => 'scheduled']);
    }

    public function test_cannot_confirm_another_organizations_appointment(): void
    {
        $this->post("/admin/appointments/{$this->otherOrganizationAppointment->public_id}/confirm")->assertForbidden();
        $this->assertSame('scheduled', $this->otherOrganizationAppointment->fresh()->status);
    }

    public function test_cannot_check_in_another_organizations_appointment(): void
    {
        $this->otherOrganizationAppointment->update(['status' => 'confirmed']);

        $this->post("/admin/appointments/{$this->otherOrganizationAppointment->public_id}/check-in")->assertForbidden();
        $this->assertSame('confirmed', $this->otherOrganizationAppointment->fresh()->status);
    }

    public function test_cannot_cancel_another_organizations_appointment(): void
    {
        $this->post("/admin/appointments/{$this->otherOrganizationAppointment->public_id}/cancel", [
            'cancellation_reason' => 'Trying to interfere',
        ])->assertForbidden();

        $this->assertSame('scheduled', $this->otherOrganizationAppointment->fresh()->status);
    }

    public function test_cannot_reschedule_another_organizations_appointment(): void
    {
        $this->post("/admin/appointments/{$this->otherOrganizationAppointment->public_id}/reschedule", [
            'appointment_date' => now()->addDays(5)->toDateString(),
            'appointment_time' => '11:00',
        ])->assertForbidden();
    }

    public function test_cannot_mark_another_organizations_appointment_no_show(): void
    {
        $this->post("/admin/appointments/{$this->otherOrganizationAppointment->public_id}/no-show")->assertForbidden();
        $this->assertSame('scheduled', $this->otherOrganizationAppointment->fresh()->status);
    }

    public function test_cannot_update_another_organizations_appointment(): void
    {
        $this->put("/admin/appointments/{$this->otherOrganizationAppointment->public_id}", [
            'reason' => 'Hacked reason',
        ])->assertForbidden();
    }

    public function test_cannot_delete_another_organizations_appointment(): void
    {
        $this->delete("/admin/appointments/{$this->otherOrganizationAppointment->public_id}")->assertForbidden();
        $this->assertDatabaseHas('appointments', ['id' => $this->otherOrganizationAppointment->id, 'deleted_at' => null]);
    }

    public function test_own_organization_appointment_can_still_be_confirmed(): void
    {
        $appointment = Appointment::factory()->create(['organization_id' => $this->ownOrganization->id, 'status' => 'scheduled']);

        $this->post("/admin/appointments/{$appointment->public_id}/confirm")->assertRedirect();
        $this->assertSame('confirmed', $appointment->fresh()->status);
    }
}
