<?php

namespace App\Services\Patients;

use App\Models\Patient;
use App\Models\PatientDuplicateCandidate;
use App\Services\SettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Weighted duplicate-detection scoring: replaces the plain LIKE-query duplicate check with a
 * configurable weighted score (0-100) across identity fields, classified as a possible or strong
 * match. Persists findings to patient_duplicate_candidates for a reviewer to action, rather than
 * only surfacing them transiently in a detect-duplicates response.
 */
class PatientDuplicateScoringService
{
    public function __construct(private SettingsService $settings) {}

    private function weights(): array
    {
        return $this->settings->get('patients.duplicate_weights', [
            'name' => 30,
            'date_of_birth' => 25,
            'phone' => 20,
            'national_identifier' => 20,
            'email' => 5,
        ]);
    }

    public function score(Patient $a, Patient $b): array
    {
        $weights = $this->weights();
        $score = 0;
        $reasons = [];

        if ($this->namesMatch($a, $b)) {
            $score += $weights['name'];
            $reasons[] = 'name';
        }

        if ($a->date_of_birth && $b->date_of_birth && $a->date_of_birth->isSameDay($b->date_of_birth)) {
            $score += $weights['date_of_birth'];
            $reasons[] = 'date_of_birth';
        }

        if ($a->phone && $b->phone && $a->phone === $b->phone) {
            $score += $weights['phone'];
            $reasons[] = 'phone';
        }

        if ($a->national_identifier && $b->national_identifier && $a->national_identifier === $b->national_identifier) {
            $score += $weights['national_identifier'];
            $reasons[] = 'national_identifier';
        }

        if ($a->email && $b->email && strcasecmp($a->email, $b->email) === 0) {
            $score += $weights['email'];
            $reasons[] = 'email';
        }

        return [min($score, 100), $reasons];
    }

    public function classify(int $score): ?string
    {
        if ($score >= 70) {
            return 'strong_match';
        }

        if ($score >= 40) {
            return 'possible_match';
        }

        return null;
    }

    private function namesMatch(Patient $a, Patient $b): bool
    {
        $normalize = fn (Patient $p) => Str::lower(trim($p->first_name.' '.$p->last_name));

        return $normalize($a) === $normalize($b);
    }

    /**
     * Scores a candidate against other active patients in the same company and persists any
     * possible/strong matches as review-queue rows (idempotent — an existing pending/actioned
     * pair for the same two patients is left alone rather than duplicated).
     */
    public function scanForCandidate(Patient $patient): Collection
    {
        $candidates = Patient::query()
            ->where('company_id', $patient->company_id)
            ->where('id', '!=', $patient->id)
            ->whereNull('merged_into_patient_id')
            ->where('status', '!=', 'merged')
            ->get();

        $created = collect();

        foreach ($candidates as $other) {
            [$score, $reasons] = $this->score($patient, $other);
            $classification = $this->classify($score);

            if (! $classification) {
                continue;
            }

            $idA = min($patient->id, $other->id);
            $idB = max($patient->id, $other->id);

            $existing = PatientDuplicateCandidate::query()
                ->where('patient_id_a', $idA)
                ->where('patient_id_b', $idB)
                ->first();

            if ($existing) {
                continue;
            }

            $created->push(PatientDuplicateCandidate::create([
                'company_id' => $patient->company_id,
                'patient_id_a' => $idA,
                'patient_id_b' => $idB,
                'score' => $score,
                'classification' => $classification,
                'status' => 'pending',
                'match_reasons' => $reasons,
            ]));
        }

        return $created;
    }
}
