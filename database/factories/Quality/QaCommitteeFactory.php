<?php

namespace Database\Factories\Quality;

use App\Models\Organization;
use App\Models\Quality\QaCommittee;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QaCommittee> */
class QaCommitteeFactory extends Factory
{
    protected $model = QaCommittee::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'name' => ucfirst(fake()->words(3, true)).' committee', 'kind' => 'safety', 'active' => true];
    }
}
