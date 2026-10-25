<?php

namespace App\Services\Ux;

use App\Models\Ipd\IpdAdmission;
use App\Models\Patient;

/** Everything the always-visible patient banner shows (guideline 19.2 rule 4). Allergies and alerts are never truncated or hidden. */
class PatientBannerData
{
    /** @return array{name: string, number: ?string, age: ?string, sex: ?string, blood_group: ?string, allergies: list<array{substance: string, severity: ?string}>, alerts: list<array{title: string, severity: string}>, location: ?string, deceased: bool} */
    public function for(Patient $patient): array
    {
        $allergies = $patient->allergies()->where('is_active', true)->orderByRaw("case severity when 'severe' then 0 when 'moderate' then 1 else 2 end")->get(['substance', 'severity']);
        $alerts = $patient->activeAlerts()->orderByRaw("case severity when 'critical' then 0 when 'warning' then 1 else 2 end")->get(['title', 'severity']);

        return [
            'name' => $patient->full_name,
            'number' => $patient->enterprise_patient_no,
            'age' => $this->age($patient),
            'sex' => $patient->sex,
            'blood_group' => $patient->blood_group,
            'allergies' => $allergies->map(fn ($a) => ['substance' => $a->substance, 'severity' => $a->severity])->all(),
            'alerts' => $alerts->map(fn ($a) => ['title' => $a->title, 'severity' => $a->severity])->all(),
            'location' => $this->location($patient),
            'deceased' => $patient->status === 'deceased',
        ];
    }

    private function age(Patient $patient): ?string
    {
        if (! $patient->date_of_birth) {
            return null;
        }

        $dob = $patient->date_of_birth;
        $now = now();

        return match (true) {
            $dob->diffInDays($now) < 28 => (int) $dob->diffInDays($now).' '.__('days'),
            $dob->diffInMonths($now) < 24 => (int) $dob->diffInMonths($now).' '.__('months'),
            default => (int) $dob->diffInYears($now).' '.__('years'),
        };
    }

    private function location(Patient $patient): ?string
    {
        $admission = IpdAdmission::query()->where('patient_id', $patient->id)->where('status', 'admitted')->with('currentAllocation.bed.room.ward')->latest('id')->first();
        $bed = $admission?->currentAllocation?->bed;

        if (! $bed) {
            return $admission ? __('Admitted') : null;
        }

        return trim(($bed->room?->ward?->name ? $bed->room->ward->name.' · ' : '').$bed->bed_code);
    }
}
