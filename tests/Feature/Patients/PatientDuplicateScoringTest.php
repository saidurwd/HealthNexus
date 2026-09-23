<?php

namespace Tests\Feature\Patients;

use App\Models\Company;
use App\Models\Patient;
use App\Models\PatientDuplicateCandidate;
use App\Services\Patients\PatientDuplicateScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientDuplicateScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_identical_name_dob_and_phone_scores_a_strong_match(): void
    {
        $company = Company::factory()->create();
        $a = Patient::factory()->create([
            'company_id' => $company->id, 'first_name' => 'Jane', 'last_name' => 'Doe',
            'date_of_birth' => '1990-01-01', 'phone' => '01711111111',
        ]);
        $b = Patient::factory()->create([
            'company_id' => $company->id, 'first_name' => 'Jane', 'last_name' => 'Doe',
            'date_of_birth' => '1990-01-01', 'phone' => '01711111111',
        ]);

        [$score, $reasons] = app(PatientDuplicateScoringService::class)->score($a, $b);

        $this->assertGreaterThanOrEqual(70, $score);
        $this->assertSame('strong_match', app(PatientDuplicateScoringService::class)->classify($score));
        $this->assertContains('name', $reasons);
        $this->assertContains('date_of_birth', $reasons);
        $this->assertContains('phone', $reasons);
    }

    public function test_completely_different_patients_score_no_match(): void
    {
        $company = Company::factory()->create();
        $a = Patient::factory()->create(['company_id' => $company->id, 'first_name' => 'Jane', 'last_name' => 'Doe', 'phone' => '01711111111', 'date_of_birth' => '1990-01-01', 'national_identifier' => null, 'email' => null]);
        $b = Patient::factory()->create(['company_id' => $company->id, 'first_name' => 'Bob', 'last_name' => 'Smith', 'phone' => '01899999999', 'date_of_birth' => '1970-05-05', 'national_identifier' => null, 'email' => null]);

        [$score] = app(PatientDuplicateScoringService::class)->score($a, $b);

        $this->assertNull(app(PatientDuplicateScoringService::class)->classify($score));
    }

    public function test_scan_for_candidate_persists_review_queue_rows_without_duplicating(): void
    {
        $company = Company::factory()->create();
        $a = Patient::factory()->create(['company_id' => $company->id, 'first_name' => 'Jane', 'last_name' => 'Doe', 'date_of_birth' => '1990-01-01', 'phone' => '01711111111']);
        $b = Patient::factory()->create(['company_id' => $company->id, 'first_name' => 'Jane', 'last_name' => 'Doe', 'date_of_birth' => '1990-01-01', 'phone' => '01711111111']);

        $service = app(PatientDuplicateScoringService::class);
        $created = $service->scanForCandidate($a);

        $this->assertCount(1, $created);
        $this->assertSame(1, PatientDuplicateCandidate::count());

        // Scanning again for the same pair must not create a duplicate review-queue row.
        $service->scanForCandidate($a);
        $this->assertSame(1, PatientDuplicateCandidate::count());

        $candidate = PatientDuplicateCandidate::first();
        $this->assertSame(min($a->id, $b->id), $candidate->patient_id_a);
        $this->assertSame(max($a->id, $b->id), $candidate->patient_id_b);
    }
}
