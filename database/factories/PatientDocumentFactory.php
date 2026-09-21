<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientDocumentFactory extends Factory
{
    protected $model = PatientDocument::class;

    public function definition(): array
    {
        return [
            'company_id' => Patient::factory(),
            'patient_id' => Patient::factory(),
            'file_name' => fake()->word().'.pdf',
            'file_path' => 'patient-documents/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(1000, 10000000),
            'document_type' => fake()->randomElement(['lab_report', 'prescription', 'referral', 'id_proof', 'insurance']),
            'description' => fake()->optional()->sentence(),
            'uploaded_by' => 1,
        ];
    }
}
