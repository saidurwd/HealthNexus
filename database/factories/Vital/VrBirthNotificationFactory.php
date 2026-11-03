<?php

namespace Database\Factories\Vital;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\Vital\VrBirthNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VrBirthNotification> */
class VrBirthNotificationFactory extends Factory
{
    protected $model = VrBirthNotification::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'number' => 'BN-'.fake()->unique()->numerify('2026-#####'), 'mother_patient_id' => Patient::factory(), 'outcome' => 'live_birth', 'sex' => fake()->randomElement(['male', 'female']), 'born_at' => now()->subDays(3), 'place_of_birth' => 'this_hospital', 'weight_g' => 3000, 'gestation_weeks' => 39, 'delivery_type' => 'normal', 'father_name' => fake()->name('male'), 'status' => 'draft'];
    }
}
