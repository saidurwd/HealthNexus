<?php

namespace Database\Factories\Welfare;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\Welfare\WfApplication;
use App\Models\Welfare\WfGrant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WfApplication> */
class WfApplicationFactory extends Factory
{
    protected $model = WfApplication::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'grant_id' => WfGrant::factory(), 'patient_id' => Patient::factory(), 'amount' => 1000, 'status' => 'requested'];
    }
}
