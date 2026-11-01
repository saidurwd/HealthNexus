<?php

namespace Database\Factories\Quality;

use App\Models\Organization;
use App\Models\Quality\QaAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QaAction> */
class QaActionFactory extends Factory
{
    protected $model = QaAction::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'number' => 'CAPA-'.fake()->unique()->numerify('######'), 'action_type' => 'corrective', 'description' => fake()->sentence(8), 'owner_id' => User::factory(), 'due_on' => now()->addDays(14)->toDateString(), 'status' => 'open'];
    }
}
