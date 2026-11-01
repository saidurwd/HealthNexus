<?php

namespace Database\Factories\Quality;

use App\Models\Organization;
use App\Models\Quality\QaAudit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QaAudit> */
class QaAuditFactory extends Factory
{
    protected $model = QaAudit::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'number' => 'AUD-'.fake()->unique()->numerify('######'), 'title' => ucfirst(fake()->words(4, true)), 'audited_on' => now()->toDateString(), 'status' => 'in_progress'];
    }
}
