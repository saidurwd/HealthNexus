<?php

namespace Database\Factories\Ed;

use App\Models\Ed\EdBay;
use App\Models\Ed\EdZone;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EdBay> */
class EdBayFactory extends Factory
{
    protected $model = EdBay::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'zone_id' => fn (array $attrs) => EdZone::factory()->create(['organization_id' => $attrs['organization_id']])->id, 'code' => strtoupper(fake()->unique()->bothify('B-##')), 'status' => EdBay::AVAILABLE, 'is_active' => true,
        ];
    }
}
