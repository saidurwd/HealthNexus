<?php

namespace Database\Factories\Quality;

use App\Models\Organization;
use App\Models\Quality\QaIncident;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QaIncident> */
class QaIncidentFactory extends Factory
{
    protected $model = QaIncident::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'number' => 'INC-'.fake()->unique()->numerify('######'), 'kind' => 'incident', 'category' => 'fall', 'harm' => 'minor', 'occurred_at' => now()->subDay(), 'description' => fake()->sentence(10), 'is_anonymous' => true, 'status' => 'reported', 'due_on' => now()->addDays(30)->toDateString()];
    }
}
