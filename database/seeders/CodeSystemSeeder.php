<?php

namespace Database\Seeders;

use App\Models\Terminology\CodeConcept;
use App\Models\Terminology\CodeSystem;
use Illuminate\Database\Seeder;

/**
 * Registers the code systems we align with (guideline 17.1) and seeds only UCUM units that are
 * verbatim UCUM case-sensitive codes. Diagnosis, lab, drug and other large vocabularies are loaded from
 * their official distribution files with `php artisan codes:import` — codes are never invented here.
 */
class CodeSystemSeeder extends Seeder
{
    public const SYSTEMS = [
        ['key' => 'icd10', 'uri' => 'http://hl7.org/fhir/sid/icd-10', 'name' => 'ICD-10', 'publisher' => 'WHO'],
        ['key' => 'icd11', 'uri' => 'http://id.who.int/icd/release/11/mms', 'name' => 'ICD-11 MMS', 'publisher' => 'WHO'],
        ['key' => 'loinc', 'uri' => 'http://loinc.org', 'name' => 'LOINC', 'publisher' => 'Regenstrief Institute'],
        ['key' => 'atc', 'uri' => 'http://www.whocc.no/atc', 'name' => 'ATC', 'publisher' => 'WHO Collaborating Centre for Drug Statistics Methodology'],
        ['key' => 'ucum', 'uri' => 'http://unitsofmeasure.org', 'name' => 'UCUM', 'publisher' => 'Regenstrief Institute'],
        ['key' => 'snomed', 'uri' => 'http://snomed.info/sct', 'name' => 'SNOMED CT', 'publisher' => 'SNOMED International'],
        ['key' => 'dgda', 'uri' => 'urn:healthnexus:dgda-drug-registry', 'name' => 'DGDA registered drugs (Bangladesh)', 'publisher' => 'Directorate General of Drug Administration'],
    ];

    /** code => display */
    private const UCUM = [
        'kg' => 'kilogram', 'g' => 'gram', 'mg' => 'milligram', 'ug' => 'microgram',
        'L' => 'liter', 'mL' => 'milliliter', 'dL' => 'deciliter',
        'm' => 'meter', 'cm' => 'centimeter', 'mm' => 'millimeter',
        's' => 'second', 'min' => 'minute', 'h' => 'hour', 'd' => 'day',
        'Cel' => 'degree Celsius', '[degF]' => 'degree Fahrenheit',
        'mm[Hg]' => 'millimeter of mercury', '%' => 'percent',
        '/min' => 'per minute', 'mmol/L' => 'millimole per liter', 'mg/dL' => 'milligram per deciliter',
        'g/dL' => 'gram per deciliter', 'mEq/L' => 'milliequivalent per liter', 'U/L' => 'unit per liter',
        'mL/h' => 'milliliter per hour', 'mg/h' => 'milligram per hour', 'ug/min' => 'microgram per minute',
        '10*3/uL' => 'thousand per microliter', '10*9/L' => 'billion per liter', 'kg/m2' => 'kilogram per square meter',
        'kPa' => 'kilopascal',
    ];

    public function run(): void
    {
        foreach (self::SYSTEMS as $system) {
            CodeSystem::firstOrCreate(['key' => $system['key']], $system + ['status' => 'active']);
        }

        $ucum = CodeSystem::where('key', 'ucum')->firstOrFail();
        foreach (self::UCUM as $code => $display) {
            CodeConcept::firstOrCreate(['code_system_id' => $ucum->id, 'code' => $code], ['display_en' => $display]);
        }
    }
}
