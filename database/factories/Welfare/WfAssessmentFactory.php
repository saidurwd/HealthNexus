<?php

namespace Database\Factories\Welfare;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\Welfare\WfAssessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WfAssessment> */
class WfAssessmentFactory extends Factory
{
    protected $model = WfAssessment::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'number' => 'WA-'.fake()->unique()->numerify('######'), 'patient_id' => Patient::factory(), 'household_size' => 4, 'monthly_income' => 8000, 'per_head' => 2000, 'score' => 0, 'category' => 'free', 'suggested_percent' => 100, 'status' => 'draft'];
    }
}
