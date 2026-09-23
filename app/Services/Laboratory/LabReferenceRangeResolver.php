<?php

namespace App\Services\Laboratory;

use App\Models\Laboratory\LabReferenceRange;
use App\Models\Laboratory\LabTest;
use App\Models\Patient;

/**
 * Resolves the single most specific applicable reference range for a test/patient combination,
 * and derives the abnormal flag from it — reference ranges are configured data
 * (lab_reference_ranges), never hardcoded per-test logic in PHP.
 */
class LabReferenceRangeResolver
{
    /**
     * @return array{low: ?string, high: ?string, text: ?string, unit: ?string}|null
     */
    public function resolve(LabTest $test, ?Patient $patient, ?string $pregnancyStatus = null): ?array
    {
        $gender = match ($patient?->sex) {
            'M' => 'male',
            'F' => 'female',
            default => 'any',
        };

        $ageYears = $patient?->date_of_birth?->diffInYears(now());
        $pregnancyStatus ??= 'any';

        $range = LabReferenceRange::query()
            ->where('test_id', $test->id)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', now()))
            ->where(fn ($q) => $q->where('gender', $gender)->orWhere('gender', 'any'))
            ->where(fn ($q) => $q->where('pregnancy_status', $pregnancyStatus)->orWhere('pregnancy_status', 'any'))
            ->where(fn ($q) => $ageYears === null
                ? $q->whereNull('age_min_years')->whereNull('age_max_years')
                : $q->where(fn ($sub) => $sub->whereNull('age_min_years')->orWhere('age_min_years', '<=', $ageYears))
                    ->where(fn ($sub) => $sub->whereNull('age_max_years')->orWhere('age_max_years', '>=', $ageYears)))
            // Most specific first: an exact gender/age/pregnancy match outranks an 'any' fallback.
            ->orderByRaw("(gender != 'any') desc")
            ->orderByRaw("(pregnancy_status != 'any') desc")
            ->orderByRaw('(age_min_years is not null or age_max_years is not null) desc')
            ->first();

        if (! $range) {
            return null;
        }

        return [
            'low' => $range->low !== null ? (string) $range->low : null,
            'high' => $range->high !== null ? (string) $range->high : null,
            'text' => $range->text_range,
            'unit' => $range->unit,
        ];
    }

    /**
     * normal|low|high|not_applicable — critical_low/critical_high are layered on top by the
     * caller (ResultEntryService) once lab_critical_values is also checked.
     */
    public function flagFor(?string $numericValue, ?array $range): string
    {
        if ($numericValue === null || $range === null || ($range['low'] === null && $range['high'] === null)) {
            return 'not_applicable';
        }

        if ($range['low'] !== null && bccomp($numericValue, $range['low'], 4) === -1) {
            return 'low';
        }

        if ($range['high'] !== null && bccomp($numericValue, $range['high'], 4) === 1) {
            return 'high';
        }

        return 'normal';
    }
}
