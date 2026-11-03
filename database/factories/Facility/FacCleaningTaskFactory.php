<?php

namespace Database\Factories\Facility;

use App\Models\Facility\FacCleaningTask;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FacCleaningTask> */
class FacCleaningTaskFactory extends Factory
{
    protected $model = FacCleaningTask::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'kind' => 'room', 'location' => 'Ward '.fake()->numberBetween(1, 6), 'priority' => 'normal', 'status' => 'pending', 'requested_at' => now(), 'checklist' => [['item' => 'Floors cleaned', 'done' => false]]];
    }
}
