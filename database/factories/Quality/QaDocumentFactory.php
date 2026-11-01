<?php

namespace Database\Factories\Quality;

use App\Models\Organization;
use App\Models\Quality\QaDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QaDocument> */
class QaDocumentFactory extends Factory
{
    protected $model = QaDocument::class;

    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'code' => 'DOC-'.fake()->unique()->numerify('####'), 'title' => ucfirst(fake()->words(3, true)), 'category' => 'policy', 'review_months' => 12, 'status' => 'active'];
    }
}
