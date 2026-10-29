<?php

namespace App\Services\Analytics\Loaders;

use App\Services\Analytics\Warehouse;

/** Finds the patient dimension row (the version in force on a date) for facts being loaded. The operational patient id never enters the warehouse. */
class PatientKeys
{
    public function __construct(private readonly Warehouse $warehouse) {}

    /**
     * @param  list<array{0:int|null,1:int,2:?string}>  $entries  [patient id, organization id, date]
     * @return list<int|null> the patient key of each entry (null when the patient is not in the dimension yet)
     */
    public function lookup(array $entries): array
    {
        $pseudonyms = [];
        foreach ($entries as $i => [$patientId, $orgId]) {
            $pseudonyms[$i] = $patientId ? $this->warehouse->pseudonym((int) $patientId, (int) $orgId) : null;
        }
        $wanted = array_values(array_unique(array_filter($pseudonyms)));
        $versions = [];
        foreach (array_chunk($wanted, 400) as $part) {
            foreach ($this->warehouse->table('dim_patient')->whereIn('pseudonym', $part)->orderBy('valid_from')->get(['patient_key', 'pseudonym', 'valid_from', 'valid_to', 'is_current']) as $v) {
                $versions[$v->pseudonym][] = $v;
            }
        }
        $out = [];
        foreach ($entries as $i => [, , $date]) {
            $list = $versions[$pseudonyms[$i]] ?? [];
            $day = $date ? substr((string) $date, 0, 10) : null;
            $pick = null;
            foreach ($list as $v) {
                if ($day === null || (substr((string) $v->valid_from, 0, 10) <= $day && ($v->valid_to === null || substr((string) $v->valid_to, 0, 10) > $day))) {
                    $pick = $v;
                }
            }
            $current = collect($list)->firstWhere('is_current', 1);
            $out[$i] = $pick->patient_key ?? ($current->patient_key ?? ($list ? end($list)->patient_key : null));
        }

        return array_values($out);
    }
}
