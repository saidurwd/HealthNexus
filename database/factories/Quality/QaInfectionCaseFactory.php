<?php

namespace Database\Factories\Quality;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\Quality\QaInfectionCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QaInfectionCase> */
class QaInfectionCaseFactory extends Factory
{
    protected $model = QaInfectionCase::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'patient_id' => fn (array $a) => Patient::factory()->create(['organization_id' => $a['organization_id']])->id, 'hai_type' => 'cauti', 'onset_on' => now()->toDateString(), 'status' => 'suspected'];
    }
}
