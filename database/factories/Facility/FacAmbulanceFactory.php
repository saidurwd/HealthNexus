<?php

namespace Database\Factories\Facility;

use App\Models\Facility\FacAmbulance;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FacAmbulance> */
class FacAmbulanceFactory extends Factory
{
    protected $model = FacAmbulance::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'registration_no' => strtoupper(fake()->unique()->bothify('DM-??-####')), 'type' => 'basic', 'status' => 'available', 'odometer_km' => 1000];
    }
}
