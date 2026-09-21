<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Company;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorScheduleTest extends TestCase
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

        Permission::create(['name' => 'manage companies', 'guard_name' => 'web']);
        $this->user->givePermissionTo('manage companies');

        $this->actingAs($this->user);

        session()->put('tenant_company_id', $this->company->id);
        session()->put('tenant_branch_id', $this->branch->id);
        app(\App\Services\TenantContextResolver::class)->setCompanyId($this->company->id);
        app(\App\Services\TenantContextResolver::class)->setBranchId($this->branch->id);
    }

    public function test_user_can_view_schedules_index(): void
    {
        $response = $this->get('/admin/schedules');

        $response->assertStatus(200);
        $response->assertViewHas('schedules');
    }

    public function test_user_can_create_schedule(): void
    {
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $department = Department::factory()->create(['company_id' => $this->company->id]);

        $response = $this->post('/admin/schedules', [
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'name' => 'Morning Schedule',
            'description' => 'Regular morning hours',
            'day_of_week' => 'monday',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'slot_duration_minutes' => 30,
        ]);

        $response->assertRedirect('/admin/schedules');
        $this->assertDatabaseHas('doctor_schedules', [
            'doctor_id' => $doctor->id,
            'name' => 'Morning Schedule',
            'day_of_week' => 'monday',
        ]);
    }

    public function test_user_can_update_schedule(): void
    {
        $schedule = DoctorSchedule::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->put('/admin/schedules/'.$schedule->id, [
            'doctor_id' => $schedule->doctor_id,
            'name' => 'Updated Schedule',
            'description' => 'Updated description',
            'day_of_week' => 'tuesday',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'slot_duration_minutes' => 45,
        ]);

        $response->assertRedirect('/admin/schedules');
        $this->assertDatabaseHas('doctor_schedules', [
            'id' => $schedule->id,
            'name' => 'Updated Schedule',
            'day_of_week' => 'tuesday',
        ]);
    }

    public function test_user_can_delete_schedule(): void
    {
        $schedule = DoctorSchedule::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->delete('/admin/schedules/'.$schedule->id);

        $response->assertRedirect('/admin/schedules');
        $this->assertSoftDeleted('doctor_schedules', ['id' => $schedule->id]);
    }
}
