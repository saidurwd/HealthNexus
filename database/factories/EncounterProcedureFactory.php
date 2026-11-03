<?php

namespace Database\Factories;

use App\Models\Encounter;
use App\Models\EncounterProcedure;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterProcedureFactory extends Factory
{
    protected $model = EncounterProcedure::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory(),
            'organization_id' => Organization::factory(),
            'procedure_code' => fake()->optional()->bothify('??##'),
            'procedure_name' => fake()->sentence(3),
            'procedure_date' => fake()->optional()->date(),
            'provider_id' => fake()->optional()->randomElement([User::factory(), null]),
            'notes' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['planned', 'completed', 'cancelled']),
            'recorded_by' => User::factory(),
            'recorded_at' => now(),
        ];
    }
}
