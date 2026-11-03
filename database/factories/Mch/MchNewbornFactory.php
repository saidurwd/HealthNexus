<?php

namespace Database\Factories\Mch;

use App\Models\Mch\MchDelivery;
use App\Models\Mch\MchNewborn;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MchNewborn> */
class MchNewbornFactory extends Factory
{
    protected $model = MchNewborn::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'delivery_id' => MchDelivery::factory(), 'birth_order' => 1, 'outcome' => 'live_birth', 'sex' => 'female', 'weight_g' => 3000, 'apgar_1' => 8, 'apgar_5' => 9];
    }
}
