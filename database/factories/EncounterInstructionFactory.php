<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterInstructionFactory extends Factory
{
    protected $model = \App\Models\EncounterInstruction::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory(),
            'company_id' => Company::factory(),
            'instruction_type' => fake()->randomElement(['medication', 'diet', 'lifestyle', 'warning', 'follow_up']),
            'content' => fake()->paragraph(),
            'created_by' => User::factory(),
        ];
    }
}
