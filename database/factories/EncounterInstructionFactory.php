<?php

namespace Database\Factories;

use App\Models\Encounter;
use App\Models\EncounterInstruction;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterInstructionFactory extends Factory
{
    protected $model = EncounterInstruction::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory(),
            'organization_id' => Organization::factory(),
            'instruction_type' => fake()->randomElement(['medication', 'diet', 'lifestyle', 'warning', 'follow_up']),
            'content' => fake()->paragraph(),
            'created_by' => User::factory(),
        ];
    }
}
