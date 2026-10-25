<?php

namespace App\Services\Search;

use App\Models\Patient;
use Illuminate\Support\Facades\DB;

class PatientSearchIndexer
{
    public function reindex(Patient $patient): void
    {
        $rows = [];
        $add = function (string $kind, ?string $term) use (&$rows, $patient) {
            if ($term !== null && $term !== '') {
                $rows[$kind.'|'.$term] = [
                    'organization_id' => $patient->organization_id, 'patient_id' => $patient->id, 'kind' => $kind, 'term' => mb_substr($term, 0, 64),
                ];
            }
        };

        $names = array_filter([$patient->first_name, $patient->middle_name, $patient->last_name, $patient->preferred_name, $patient->attributes['display_name'] ?? null]);
        foreach (Normalizer::nameTokens(implode(' ', $names)) as $token) {
            $add('name', $token);
            $add('phonetic', Normalizer::phonetic($token));
        }

        $add('phone', Normalizer::phone($patient->phone));
        foreach (DB::table('patient_contacts')->where('patient_id', $patient->id)->pluck('phone') as $phone) {
            $add('phone', Normalizer::phone($phone));
        }

        $add('nid', Normalizer::code($patient->national_identifier));
        foreach (DB::table('patient_identifiers')->where('patient_id', $patient->id)->whereNull('deleted_at')->pluck('identifier_value') as $value) {
            $add('nid', Normalizer::code($value));
        }

        $localNumbers = DB::table('patient_branch_registrations')->where('patient_id', $patient->id)->pluck('local_patient_no');
        foreach ([$patient->enterprise_patient_no, ...$localNumbers] as $mrn) {
            $code = Normalizer::code($mrn);
            $add('mrn', $code);
            $add('mrn', $code !== null ? ltrim(preg_replace('/\D+/', '', $code), '0') : null); // "123" finds DHC-00000123
        }

        DB::transaction(function () use ($patient, $rows) {
            DB::table('patient_search_index')->where('patient_id', $patient->id)->delete();
            foreach (array_chunk(array_values($rows), 200) as $chunk) {
                DB::table('patient_search_index')->insert($chunk);
            }
        });
    }

    public function forget(Patient $patient): void
    {
        DB::table('patient_search_index')->where('patient_id', $patient->id)->delete();
    }
}
