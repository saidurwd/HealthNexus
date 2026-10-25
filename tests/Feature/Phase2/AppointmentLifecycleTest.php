<?php

namespace Tests\Feature\Phase2;

use App\Models\Appointment;
use App\Models\AppointmentNote;
use App\Models\AppointmentStatusHistory;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AppointmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organization $organization;
    private Branch $branch;
    private Patient $patient;
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->patient = Patient::factory()->create(['organization_id' => $this->organization->id]);

        $this->user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);

        foreach ([
            'manage organizations', 'appointments.view', 'appointments.update', 'appointments.confirm',
            'appointments.checkin', 'appointments.cancel', 'appointments.reschedule', 'appointments.no_show',
        ] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->appointment = Appointment::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->user);
        session()->put('tenant_organization_id', $this->organization->id);
        session()->put('tenant_branch_id', $this->branch->id);
        app(\App\Services\TenantContextResolver::class)->setOrganizationId($this->organization->id);
        app(\App\Services\TenantContextResolver::class)->setBranchId($this->branch->id);
    }

    public function test_reschedule_records_history(): void
    {
        $this->post("/admin/appointments/{$this->appointment->id}/reschedule", [
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_time' => '11:00',
            'reason' => 'Patient requested morning slot',
        ])->assertRedirect();

        $this->appointment->refresh();
        $this->assertSame('scheduled', $this->appointment->status);
        $this->assertDatabaseHas('appointment_status_histories', [
            'appointment_id' => $this->appointment->id,
            'old_status' => 'scheduled',
            'new_status' => 'scheduled',
            'reason' => 'Patient requested morning slot',
        ]);
    }

    public function test_cancel_records_history_and_timestamps(): void
    {
        $this->post("/admin/appointments/{$this->appointment->id}/cancel", [
            'cancellation_reason' => 'Doctor unavailable',
        ])->assertRedirect();

        $this->appointment->refresh();
        $this->assertSame('cancelled', $this->appointment->status);
        $this->assertNotNull($this->appointment->cancelled_at);
        $this->assertEquals($this->user->id, $this->appointment->cancelled_by);
        $this->assertDatabaseHas('appointment_status_histories', [
            'appointment_id' => $this->appointment->id,
            'new_status' => 'cancelled',
        ]);
    }

    public function test_confirm_sets_timestamp_and_user(): void
    {
        $this->post("/admin/appointments/{$this->appointment->id}/confirm")->assertRedirect();

        $this->appointment->refresh();
        $this->assertSame('confirmed', $this->appointment->status);
        $this->assertNotNull($this->appointment->confirmed_at);
        $this->assertEquals($this->user->id, $this->appointment->confirmed_by);
        $this->assertDatabaseHas('appointment_status_histories', [
            'appointment_id' => $this->appointment->id,
            'new_status' => 'confirmed',
        ]);
    }

    public function test_check_in_sets_timestamp_and_user(): void
    {
        $this->post("/admin/appointments/{$this->appointment->id}/confirm");

        $this->post("/admin/appointments/{$this->appointment->id}/check-in")->assertRedirect();

        $this->appointment->refresh();
        $this->assertSame('checked_in', $this->appointment->status);
        $this->assertNotNull($this->appointment->checked_in_at);
        $this->assertEquals($this->user->id, $this->appointment->checked_in_by);
        $this->assertNotNull($this->appointment->actual_datetime);
        $this->assertDatabaseHas('appointment_status_histories', [
            'appointment_id' => $this->appointment->id,
            'new_status' => 'checked_in',
        ]);
    }

    public function test_complete_sets_timestamps(): void
    {
        $this->post("/admin/appointments/{$this->appointment->id}/confirm");
        $this->post("/admin/appointments/{$this->appointment->id}/check-in");
        $this->post("/admin/appointments/{$this->appointment->id}/start");

        $this->post("/admin/appointments/{$this->appointment->id}/complete")->assertRedirect();

        $this->appointment->refresh();
        $this->assertSame('completed', $this->appointment->status);
        $this->assertNotNull($this->appointment->completed_at);
        $this->assertNotNull($this->appointment->ended_at);
        $this->assertDatabaseHas('appointment_status_histories', [
            'appointment_id' => $this->appointment->id,
            'new_status' => 'completed',
        ]);
    }

    public function test_no_show_updates_status(): void
    {
        $this->post("/admin/appointments/{$this->appointment->id}/no-show", [
            'reason' => 'Patient did not arrive',
        ])->assertRedirect();

        $this->appointment->refresh();
        $this->assertSame('no_show', $this->appointment->status);
        $this->assertNotNull($this->appointment->no_show_at);
        $this->assertDatabaseHas('appointment_status_histories', [
            'appointment_id' => $this->appointment->id,
            'new_status' => 'no_show',
        ]);
    }

    public function test_admin_can_add_appointment_note(): void
    {
        $this->post("/admin/appointments/{$this->appointment->id}/notes", [
            'note' => 'Patient requested afternoon slot.',
        ])->assertRedirect();

        $this->assertDatabaseHas('appointment_notes', [
            'appointment_id' => $this->appointment->id,
            'note' => 'Patient requested afternoon slot.',
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_history_page_shows_transitions(): void
    {
        $this->post("/admin/appointments/{$this->appointment->id}/confirm");

        $this->get("/admin/appointments/{$this->appointment->id}/history")
            ->assertOk()
            ->assertSee('confirmed');
    }

    public function test_check_in_cannot_be_skipped_directly_from_scheduled(): void
    {
        $this->post("/admin/appointments/{$this->appointment->id}/check-in")
            ->assertSessionHasErrors('status');

        $this->assertSame('scheduled', $this->appointment->fresh()->status);
    }
}
