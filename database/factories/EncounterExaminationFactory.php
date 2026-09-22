<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterExaminationFactory extends Factory
{
    protected $model = \App\Models\EncounterExamination::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory(),
            'company_id' => Company::factory(),
            'section_name' => fake()->randomElement(['General', 'CVS', 'RS', 'Abdomen', 'Neurological', 'Musculoskeletal', 'Skin', 'HEENT']),
            'findings' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
