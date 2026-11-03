<?php

namespace Database\Factories\Blood;

use App\Models\Blood\BloodUnit;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BloodUnit> A bare bag row (available, tested group). Use the donation services for a bag that went through the real chain. */
class BloodUnitFactory extends Factory
{
    protected $model = BloodUnit::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(), 'unit_number' => strtoupper(fake()->unique()->bothify('DON-####-########-##')), 'component' => 'prbc',
            'abo' => fake()->randomElement(['O', 'A', 'B', 'AB']), 'rh' => fake()->randomElement(['positive', 'negative']), 'volume_ml' => 280, 'status' => BloodUnit::AVAILABLE,
            'collected_at' => now()->subDays(3), 'expires_at' => now()->addDays(30), 'released_at' => now()->subDays(2),
        ];
    }

    public function component(string $component): static
    {
        return $this->state(fn () => ['component' => $component, 'volume_ml' => config("blood.components.$component.volume"), 'expires_at' => now()->addDays((int) config("blood.components.$component.shelf_days") - 3)]);
    }

    public function status(string $status): static
    {
        return $this->state(['status' => $status]);
    }
}
