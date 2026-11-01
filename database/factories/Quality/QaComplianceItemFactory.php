<?php

namespace Database\Factories\Quality;

use App\Models\Organization;
use App\Models\Quality\QaComplianceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QaComplianceItem> */
class QaComplianceItemFactory extends Factory
{
    protected $model = QaComplianceItem::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'kind' => 'licence', 'title' => ucfirst(fake()->words(3, true)), 'due_on' => now()->addDays(90)->toDateString(), 'status' => 'active'];
    }
}
