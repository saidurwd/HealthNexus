<?php

namespace Database\Factories\Welfare;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\Welfare\WfAssessment;
use App\Models\Welfare\WfFund;
use App\Models\Welfare\WfGrant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WfGrant> */
class WfGrantFactory extends Factory
{
    protected $model = WfGrant::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'number' => 'WG-'.fake()->unique()->numerify('######'), 'patient_id' => Patient::factory(), 'assessment_id' => WfAssessment::factory(), 'fund_id' => WfFund::factory(), 'cap_amount' => 10000, 'percent' => 100, 'valid_from' => now()->toDateString(), 'valid_to' => now()->addDays(90)->toDateString(), 'status' => 'requested'];
    }
}
