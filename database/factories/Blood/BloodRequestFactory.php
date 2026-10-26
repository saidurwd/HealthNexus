<?php

namespace Database\Factories\Blood;

use App\Models\Blood\BloodRequest;
use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BloodRequest> */
class BloodRequestFactory extends Factory
{
    protected $model = BloodRequest::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'patient_id' => fn (array $a) => Patient::factory()->create(['organization_id' => $a['organization_id']])->id,
            'request_number' => strtoupper(fake()->unique()->bothify('BRQ-####-########')), 'source_module' => 'ipd', 'component' => 'prbc', 'units_requested' => 1,
            'priority' => 'routine', 'status' => 'requested', 'requested_at' => now(),
        ];
    }

    public function priority(string $priority): static
    {
        return $this->state(['priority' => $priority]);
    }
}
