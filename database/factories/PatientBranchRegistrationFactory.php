<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientBranchRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientBranchRegistrationFactory extends Factory
{
    protected $model = PatientBranchRegistration::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'patient_id' => Patient::factory(),
            'branch_id' => Branch::factory(),
            'local_patient_no' => fake()->unique()->numerify('LPN-########'),
            'registered_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'status' => 'active',
        ];
    }
}
