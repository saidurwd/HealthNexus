<?php

namespace Database\Factories\Watch;

use App\Models\Organization;
use App\Models\Watch\SvCondition;
use App\Models\Watch\SvSignal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SvSignal> */
class SvSignalFactory extends Factory
{
    protected $model = SvSignal::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'condition_id' => SvCondition::factory(), 'week_start' => now()->startOfWeek()->toDateString(), 'window_from' => now()->subDays(6)->toDateString(), 'window_to' => now()->toDateString(), 'cases' => 5, 'baseline' => 1.5, 'reason' => 'spike', 'status' => 'new', 'raised_at' => now()];
    }
}
