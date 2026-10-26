<?php

namespace App\Services\Blood;

use App\Models\Blood\BloodDonor;
use App\Models\Blood\BloodDonorScreening;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\User;
use App\Services\Numbering\NumberSeriesService;
use App\Services\Patients\PatientService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Donor registry, consent, eligibility screening and deferral. The eligibility rules are policy (editable), not code. */
class BloodDonorService
{
    public function __construct(
        private readonly PatientService $patients,
        private readonly NumberSeriesService $numbers,
        private readonly BloodPolicy $policy,
    ) {}

    /** @param  array<string, mixed>  $person  patient fields (first_name, last_name, sex, date_of_birth, phone …) or ['patient_id' => n] for someone already registered */
    public function register(User $by, Branch $branch, array $person, bool $replacement = false): BloodDonor
    {
        return DB::transaction(function () use ($by, $branch, $person, $replacement) {
            $patient = isset($person['patient_id'])
                ? Patient::query()->where('organization_id', $branch->organization_id)->findOrFail($person['patient_id'])
                : $this->patients->createPatient($person + ['organization_id' => $branch->organization_id], $by, $branch);

            if (BloodDonor::query()->where('organization_id', $branch->organization_id)->where('patient_id', $patient->id)->exists()) {
                throw ValidationException::withMessages(['patient' => 'This person is already on the donor register.']);
            }

            return BloodDonor::create([
                'organization_id' => $branch->organization_id, 'patient_id' => $patient->id, 'is_replacement' => $replacement, 'status' => BloodDonor::ELIGIBLE,
                'donor_number' => $this->numbers->next('blood.donor', $branch->organization_id, null, prefix: 'DNR', format: '{PREFIX}-{YEAR}-{SEQ:6}', userId: $by->id),
            ]);
        });
    }

    public function recordConsent(BloodDonor $donor, User $by): BloodDonor
    {
        $donor->update(['consent_at' => now(), 'consent_by' => $by->id]);

        return $donor->refresh();
    }

    /**
     * Applies the eligibility rules to the measurements and the questionnaire. A failed rule defers the donor (for the period the policy gives
     * that reason, or for good); a deferred donor cannot proceed to donation until the date passes.
     *
     * @param  array{weight_kg?: float, hemoglobin?: float, systolic?: int, diastolic?: int, pulse?: int, temperature_c?: float, questionnaire?: array<string, bool>}  $data  a questionnaire entry set to true means "yes, this applies" (e.g. recent_illness)
     */
    public function screen(BloodDonor $donor, User $by, array $data): BloodDonorScreening
    {
        if ($donor->consent_at === null) {
            throw ValidationException::withMessages(['consent' => 'The donor has not given consent.']);
        }

        return DB::transaction(function () use ($donor, $by, $data) {
            $locked = BloodDonor::query()->lockForUpdate()->findOrFail($donor->id);
            $patient = Patient::findOrFail($locked->patient_id);
            [$failed, $reason] = $this->evaluate($locked, $patient, $data);
            $deferrals = $this->policy->get('deferral_reasons');
            $rule = $deferrals[$reason] ?? 30;
            $until = $failed === [] ? null : ($rule === 'permanent' ? null : ($reason === 'recent_donation' ? null : now()->addDays((int) $rule)->toDateString()));

            $screening = BloodDonorScreening::create([
                'organization_id' => $locked->organization_id, 'donor_id' => $locked->id, 'weight_kg' => $data['weight_kg'] ?? null, 'hemoglobin' => $data['hemoglobin'] ?? null,
                'systolic' => $data['systolic'] ?? null, 'diastolic' => $data['diastolic'] ?? null, 'pulse' => $data['pulse'] ?? null, 'temperature_c' => $data['temperature_c'] ?? null,
                'questionnaire' => $data['questionnaire'] ?? [], 'outcome' => $failed === [] ? 'eligible' : 'deferred', 'failed_rules' => $failed ?: null,
                'deferral_reason' => $reason, 'deferred_until' => $reason === 'recent_donation' ? $this->nextEligibleDate($locked, $patient)?->toDateString() : $until,
                'screened_at' => now(), 'screened_by' => $by->id,
            ]);

            if ($failed !== []) {
                $permanent = ($deferrals[$reason] ?? null) === 'permanent';
                $locked->update(['status' => $permanent ? BloodDonor::PERMANENTLY_DEFERRED : BloodDonor::DEFERRED, 'deferral_reason' => $reason, 'deferred_until' => $permanent ? null : $screening->deferred_until]);
            } elseif ($locked->status === BloodDonor::DEFERRED) {
                $locked->update(['status' => BloodDonor::ELIGIBLE, 'deferral_reason' => null, 'deferred_until' => null]);
            }

            return $screening;
        });
    }

    /** @return array{0: list<string>, 1: ?string} failed rules and the reason that decides the deferral */
    private function evaluate(BloodDonor $donor, Patient $patient, array $d): array
    {
        $rules = $this->policy->get('eligibility');
        $sex = $patient->sex === 'F' ? 'F' : 'M';
        $failed = [];
        $reason = null;
        $fail = function (string $rule, string $why) use (&$failed, &$reason) {
            $failed[] = $rule;
            $reason ??= $why;
        };

        $dob = $patient->getAttribute('date_of_birth');
        $age = $dob ? $dob->age : $patient->getAttribute('estimated_age');
        if ($age !== null && ($age < $rules['min_age'] || $age > $rules['max_age'])) {
            $fail('age', 'other');
        }
        if (isset($d['weight_kg']) && $d['weight_kg'] < $rules['min_weight_kg']) {
            $fail('weight', 'underweight');
        }
        if (isset($d['hemoglobin']) && $d['hemoglobin'] < $rules['min_hb'][$sex]) {
            $fail('hemoglobin', 'low_hemoglobin');
        }
        if (isset($d['pulse']) && ($d['pulse'] < $rules['pulse'][0] || $d['pulse'] > $rules['pulse'][1])) {
            $fail('pulse', 'other');
        }
        if (isset($d['systolic']) && ($d['systolic'] < $rules['systolic'][0] || $d['systolic'] > $rules['systolic'][1])) {
            $fail('blood_pressure', 'other');
        }
        if (isset($d['diastolic']) && ($d['diastolic'] < $rules['diastolic'][0] || $d['diastolic'] > $rules['diastolic'][1])) {
            $fail('blood_pressure', 'other');
        }
        if (isset($d['temperature_c']) && $d['temperature_c'] > $rules['max_temp_c']) {
            $fail('temperature', 'recent_illness');
        }
        if ($donor->last_donation_at && $donor->last_donation_at->gt(now()->subDays((int) $rules['min_interval_days'][$sex]))) {
            $fail('interval', 'recent_donation');
        }
        foreach (($d['questionnaire'] ?? []) as $key => $yes) {
            if ($yes && array_key_exists($key, $this->policy->get('deferral_reasons'))) {
                $fail("questionnaire:$key", $key);
            }
        }

        return [$failed, $reason];
    }

    private function nextEligibleDate(BloodDonor $donor, Patient $patient): ?\Carbon\CarbonInterface
    {
        $days = (int) $this->policy->get('eligibility')['min_interval_days'][$patient->sex === 'F' ? 'F' : 'M'];

        return $donor->last_donation_at?->copy()->addDays($days);
    }
}
