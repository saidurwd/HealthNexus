<?php

/*
| Observation catalogue (guideline 10.4). Codes are the published LOINC codes for the common vital signs;
| units are UCUM. `min`/`max` are hard plausibility limits (typing mistakes), NOT clinical alert thresholds —
| alerts belong to the Alert Service. `aliases` map the free-text types used by the legacy Nursing and ICU
| tables so the read-through views can find them.
*/
return [
    'vitals' => [
        'body_temperature' => ['code' => '8310-5', 'display' => 'Body temperature', 'unit' => 'Cel', 'min' => 25, 'max' => 45,
            'aliases' => ['temperature', 'temp', 'body_temperature']],
        'heart_rate' => ['code' => '8867-4', 'display' => 'Heart rate', 'unit' => '/min', 'min' => 0, 'max' => 350,
            'aliases' => ['heart_rate', 'hr', 'pulse', 'pulse_rate']],
        'respiratory_rate' => ['code' => '9279-1', 'display' => 'Respiratory rate', 'unit' => '/min', 'min' => 0, 'max' => 120,
            'aliases' => ['respiratory_rate', 'rr', 'resp_rate']],
        'oxygen_saturation' => ['code' => '2708-6', 'display' => 'Oxygen saturation in Arterial blood', 'unit' => '%', 'min' => 0, 'max' => 100,
            'aliases' => ['spo2', 'oxygen_saturation', 'o2_sat']],
        'systolic_bp' => ['code' => '8480-6', 'display' => 'Systolic blood pressure', 'unit' => 'mm[Hg]', 'min' => 0, 'max' => 350,
            'aliases' => ['systolic', 'systolic_bp', 'sbp']],
        'diastolic_bp' => ['code' => '8462-4', 'display' => 'Diastolic blood pressure', 'unit' => 'mm[Hg]', 'min' => 0, 'max' => 250,
            'aliases' => ['diastolic', 'diastolic_bp', 'dbp']],
        'body_weight' => ['code' => '29463-7', 'display' => 'Body weight', 'unit' => 'kg', 'min' => 0.2, 'max' => 700,
            'aliases' => ['weight', 'body_weight']],
        'body_height' => ['code' => '8302-2', 'display' => 'Body height', 'unit' => 'cm', 'min' => 20, 'max' => 280,
            'aliases' => ['height', 'body_height']],
        'bmi' => ['code' => '39156-5', 'display' => 'Body mass index (BMI)', 'unit' => 'kg/m2', 'min' => 5, 'max' => 150,
            'aliases' => ['bmi']],
    ],

    'panels' => [
        'blood_pressure' => ['code' => '85354-9', 'members' => ['systolic_bp', 'diastolic_bp']],
    ],

    'contexts' => ['opd', 'ipd', 'icu', 'ed', 'nursing', 'ot'],
];
