<?php

namespace Database\Factories\Ed;

use App\Models\Branch;
use App\Models\Ed\EdVisit;
use App\Models\Encounter;
use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EdVisit> A bare visit row (awaiting triage). Use EdVisitService::arrive() for a visit that went through the real flow. */
class EdVisitFactory extends Factory
{
    protected $model = EdVisit::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'branch_id' => fn (array $a) => Branch::factory()->create(['organization_id' => $a['organization_id']])->id,
            'patient_id' => fn (array $a) => Patient::factory()->create(['organization_id' => $a['organization_id']])->id,
            'encounter_id' => fn (array $a) => Encounter::factory()->create(['organization_id' => $a['organization_id'], 'branch_id' => $a['branch_id'], 'patient_id' => $a['patient_id']])->id,
            'visit_number' => strtoupper(fake()->unique()->bothify('EDV-####-########')), 'arrival_at' => now(), 'arrival_mode' => 'walk_in',
            'chief_complaint' => fake()->sentence(3), 'status' => EdVisit::AWAITING_TRIAGE, 'triage_due_at' => now()->addMinutes(10),
        ];
    }

    public function triaged(int $level = 3): static
    {
        return $this->state(fn () => ['status' => EdVisit::WAITING, 'triage_level' => $level, 'triaged_at' => now()]);
    }
}
