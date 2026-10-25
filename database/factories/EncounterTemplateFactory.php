<?php

namespace Database\Factories;

use App\Models\EncounterTemplate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterTemplateFactory extends Factory
{
    protected $model = EncounterTemplate::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['General Medicine OPD', 'Pediatric Examination', 'Cardiology Examination', 'Dermatology Examination', 'Orthopedic Examination', 'Gynecology Examination']),
            'specialty' => fake()->optional()->randomElement(['General Medicine', 'Pediatrics', 'Cardiology', 'Dermatology', 'Orthopedics', 'Gynecology']),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}
