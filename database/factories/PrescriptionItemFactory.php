<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionItemFactory extends Factory
{
    protected $model = PrescriptionItem::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'prescription_id' => Prescription::factory(),
            'medicine_name' => fake()->word(),
            'dosage_form' => fake()->randomElement(['tablet', 'capsule', 'liquid', 'injection', 'cream']),
            'strength' => fake()->numberBetween(1, 500).'mg',
            'frequency' => fake()->randomElement(['BID', 'TID', 'QID', 'OD', 'PRN']),
            'duration' => fake()->numberBetween(1, 14).' days',
            'quantity' => fake()->numberBetween(1, 100),
            'instructions' => fake()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
