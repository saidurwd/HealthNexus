<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Encounter;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterReviewOfSystemFactory extends Factory
{
    protected $model = \App\Models\EncounterReviewOfSystem::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory(),
            'organization_id' => Organization::factory(),
            'system_name' => fake()->randomElement(['Constitutional', 'Respiratory', 'Cardiovascular', 'GI', 'GU', 'Neurological', 'Musculoskeletal', 'Skin', 'Psychiatric']),
            'status' => fake()->randomElement(['normal', 'abnormal', 'not_assessed']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
