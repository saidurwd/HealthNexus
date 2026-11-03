<?php

namespace Database\Factories\Vital;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\Vital\VrMedicolegalCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VrMedicolegalCase> */
class VrMedicolegalCaseFactory extends Factory
{
    protected $model = VrMedicolegalCase::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'number' => 'ML-'.fake()->unique()->numerify('2026-#####'), 'patient_id' => Patient::factory(), 'kind' => 'accident', 'brought_at' => now()->subHours(2), 'description' => 'Road traffic accident; brought by bystanders.', 'status' => 'open'];
    }
}
