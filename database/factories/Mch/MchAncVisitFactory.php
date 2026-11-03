<?php

namespace Database\Factories\Mch;

use App\Models\Mch\MchAncVisit;
use App\Models\Mch\MchPregnancy;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MchAncVisit> */
class MchAncVisitFactory extends Factory
{
    protected $model = MchAncVisit::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'pregnancy_id' => MchPregnancy::factory(), 'visit_no' => 1, 'visited_at' => now()->subWeeks(4), 'weeks' => 14, 'bp_systolic' => 110, 'bp_diastolic' => 70, 'weight_kg' => 58.5, 'hemoglobin' => 11.8];
    }
}
