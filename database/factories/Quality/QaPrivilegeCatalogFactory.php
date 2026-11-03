<?php

namespace Database\Factories\Quality;

use App\Models\Organization;
use App\Models\Quality\QaPrivilegeCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QaPrivilegeCatalog> */
class QaPrivilegeCatalogFactory extends Factory
{
    protected $model = QaPrivilegeCatalog::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => strtoupper(fake()->unique()->lexify('P-????')), 'name' => ucfirst(fake()->words(2, true)), 'validity_months' => 24, 'active' => true];
    }
}
