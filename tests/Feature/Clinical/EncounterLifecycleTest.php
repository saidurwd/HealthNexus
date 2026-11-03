<?php

namespace Tests\Feature\Clinical;

use App\Models\Branch;
use App\Models\Encounter;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\EncounterLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncounterLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Encounter $encounter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $organization = Organization::factory()->create();
        $branch = Branch::factory()->create(['organization_id' => $organization->id]);
        $patient = Patient::factory()->create(['organization_id' => $organization->id]);

        $this->user->organizations()->attach($organization->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($branch->id, ['access_level' => 'staff', 'organization_id' => $organization->id]);

        $this->encounter = Encounter::factory()->create([
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
            'patient_id' => $patient->id,
            'status' => 'registered',
        ]);
    }

    public function test_can_transition_from_registered_to_waiting(): void
    {
        $service = app(EncounterLifecycleService::class);

        $service->moveTo($this->encounter->fresh(), 'waiting', $this->user);

        $this->assertDatabaseHas('encounters', ['id' => $this->encounter->id, 'status' => 'waiting']);
    }

    public function test_cannot_transition_from_registered_to_completed(): void
    {
        $service = app(EncounterLifecycleService::class);

        $this->expectException(\InvalidArgumentException::class);

        $service->moveTo($this->encounter->fresh(), 'completed', $this->user);
    }

    public function test_can_complete_encounter_from_in_progress(): void
    {
        $this->encounter->update(['status' => 'in_progress']);

        $service = app(EncounterLifecycleService::class);
        $service->complete($this->encounter->fresh(), $this->user);

        $this->assertDatabaseHas('encounters', ['id' => $this->encounter->id, 'status' => 'completed']);
    }

    public function test_can_lock_completed_encounter(): void
    {
        $this->encounter->update(['status' => 'completed']);

        $service = app(EncounterLifecycleService::class);
        $service->lock($this->encounter->fresh(), $this->user);

        $this->assertDatabaseHas('encounters', ['id' => $this->encounter->id, 'status' => 'locked']);
    }
}
