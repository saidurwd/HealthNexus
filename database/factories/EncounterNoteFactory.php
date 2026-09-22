<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterNoteFactory extends Factory
{
    protected $model = \App\Models\EncounterNote::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory(),
            'company_id' => Company::factory(),
            'section' => fake()->optional()->randomElement(['chief_complaint', 'hpi', 'examination', 'assessment', 'plan']),
            'content' => fake()->paragraph(),
            'created_by' => User::factory(),
        ];
    }
}
