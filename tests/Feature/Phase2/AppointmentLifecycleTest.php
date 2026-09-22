<?php

namespace Tests\Feature\Phase2;

use App\Models\Appointment;
use App\Models\AppointmentNote;
use App\Models\AppointmentStatusHistory;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AppointmentLifecycleTest extends TestCase
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

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);

        Permission::create(['name' => 'manage companies', 'guard_name' => 'web']);
        Permission::create(['name' => 'appointments.update', 'guard_name' => 'web']);
        $this->user->givePermissionTo('manage companies');
        $this->user->givePermissionTo('appointments.update');

        $this->appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $this->patient->id,
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->user);
        session()->put('tenant_company_id', $this->company->id);
        session()->put('tenant_branch_id', $this->branch->id);
        app(\App\Services\TenantContextResolver::class)->setCompanyId($this->company->id);
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
            'company_id' => $this->company->id,
        ]);
    }

    public function test_history_page_shows_transitions(): void
    {
        $this->appointment->update(['status' => 'confirmed']);
        app(\App\Services\Appointments\AppointmentService::class)->recordHistory($this->appointment, 'confirmed');

        $this->get("/admin/appointments/{$this->appointment->id}/history")
            ->assertOk()
            ->assertSee('confirmed');
    }
}
