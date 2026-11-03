<?php

namespace Database\Factories\Mch;

use App\Models\Mch\MchImmunisation;
use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MchImmunisation> */
class MchImmunisationFactory extends Factory
{
    protected $model = MchImmunisation::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'patient_id' => Patient::factory(), 'vaccine' => 'bcg', 'given_at' => now()->subDays(10), 'status' => 'given'];
    }
}
