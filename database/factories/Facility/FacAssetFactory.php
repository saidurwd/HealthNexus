<?php

namespace Database\Factories\Facility;

use App\Models\Facility\FacAsset;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FacAsset> */
class FacAssetFactory extends Factory
{
    protected $model = FacAsset::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'tag' => 'EQ-'.fake()->unique()->numerify('#####'), 'name' => ucfirst(fake()->words(2, true)), 'category' => 'biomedical', 'risk_class' => 'medium', 'status' => 'in_service', 'qr_token' => bin2hex(random_bytes(16))];
    }
}
