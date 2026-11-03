<?php

namespace Database\Factories\Mch;

use App\Models\Mch\MchDelivery;
use App\Models\Mch\MchPregnancy;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MchDelivery> */
class MchDeliveryFactory extends Factory
{
    protected $model = MchDelivery::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'pregnancy_id' => MchPregnancy::factory(), 'delivered_at' => now()->subDays(2), 'weeks' => 39, 'mode' => 'normal', 'place' => 'this_hospital', 'mother_outcome' => 'well'];
    }
}
