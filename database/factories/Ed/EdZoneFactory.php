<?php

namespace Database\Factories\Ed;

use App\Models\Branch;
use App\Models\Ed\EdZone;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EdZone> */
class EdZoneFactory extends Factory
{
    protected $model = EdZone::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'branch_id' => fn (array $attrs) => Branch::factory()->create(['organization_id' => $attrs['organization_id']])->id,
            'code' => strtoupper(fake()->unique()->lexify('Z???')), 'name' => fake()->words(2, true), 'zone_type' => fake()->randomElement(config('ed.zone_types')),
            'sort_order' => 0, 'is_active' => true,
        ];
    }
}
