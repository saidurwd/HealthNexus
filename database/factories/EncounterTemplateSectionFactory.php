<?php

namespace Database\Factories;

use App\Models\EncounterTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterTemplateSectionFactory extends Factory
{
    protected $model = \App\Models\EncounterTemplateSection::class;

    public function definition(): array
    {
        return [
            'encounter_template_id' => EncounterTemplate::factory(),
            'section_name' => fake()->randomElement(['Chief Complaint', 'HPI', 'ROS', 'General Examination', 'Cardiovascular', 'Respiratory', 'Abdomen', 'Assessment', 'Plan']),
            'section_key' => fake()->slug(),
            'sort_order' => fake()->numberBetween(1, 10),
            'is_required' => fake()->boolean(70),
        ];
    }
}
