<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterDocumentFactory extends Factory
{
    protected $model = \App\Models\EncounterDocument::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory(),
            'company_id' => Company::factory(),
            'document_type' => fake()->randomElement(['consultation_summary', 'referral_letter', 'prescription', 'clinical_certificate', 'medical_note']),
            'file_name' => fake()->word().'.pdf',
            'file_path' => 'documents/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(1000, 500000),
            'created_by' => User::factory(),
        ];
    }
}
