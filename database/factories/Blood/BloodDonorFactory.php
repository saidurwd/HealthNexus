<?php

namespace Database\Factories\Blood;

use App\Models\Blood\BloodDonor;
use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BloodDonor> */
class BloodDonorFactory extends Factory
{
    protected $model = BloodDonor::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'patient_id' => fn (array $a) => Patient::factory()->create(['organization_id' => $a['organization_id']])->id,
            'donor_number' => strtoupper(fake()->unique()->bothify('DNR-####-######')), 'abo' => fake()->randomElement(['O', 'A', 'B', 'AB']), 'rh' => 'positive',
            'status' => BloodDonor::ELIGIBLE, 'consent_at' => now(), 'donation_count' => 0,
        ];
    }

    public function deferred(int $days = 30): static
    {
        return $this->state(['status' => BloodDonor::DEFERRED, 'deferral_reason' => 'low_hemoglobin', 'deferred_until' => now()->addDays($days)->toDateString()]);
    }
}
