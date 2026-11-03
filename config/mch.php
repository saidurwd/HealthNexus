<?php

/*
| Maternal and child health (Phase 26, B4). Every figure here is an EXAMPLE for the hospital to confirm with its obstetrics and paediatrics heads and with the national programme (EPI, DGHS): Settings (`mch.<key>`) override it.
| The immunisation schedule below is a commonly used routine schedule written from general knowledge, NOT the current official EPI Bangladesh schedule - the programme must confirm every dose and age before use.
*/
return [
    // Antenatal care: the planned number of contacts and the weeks they are usually due.
    'anc_visit_weeks' => [12, 20, 26, 32, 36, 38],

    // Readings that raise an alert at a visit.
    'alerts' => [
        'bp_systolic_high' => 140, 'bp_diastolic_high' => 90, 'bp_systolic_severe' => 160, 'bp_diastolic_severe' => 110,
        'hemoglobin_low' => 11.0, 'hemoglobin_severe' => 7.0,
        'fhr_low' => 110, 'fhr_high' => 160,
    ],

    // Chosen at booking: each makes the pregnancy high-risk.
    'risk_factors' => ['age_under_18', 'age_over_35', 'previous_caesarean', 'previous_stillbirth', 'hypertension', 'diabetes', 'anaemia', 'twins', 'short_interval', 'bad_obstetric_history', 'hiv_or_hepatitis'],
    // Observed at a visit: any one needs action the same day.
    'danger_signs' => ['bleeding', 'severe_headache', 'blurred_vision', 'convulsions', 'reduced_fetal_movement', 'fever', 'leaking_fluid', 'severe_abdominal_pain'],
    'delivery_modes' => ['normal', 'caesarean', 'assisted', 'breech'],
    'delivery_complications' => ['postpartum_haemorrhage', 'eclampsia', 'obstructed_labour', 'sepsis', 'perineal_tear_3_4', 'retained_placenta', 'shoulder_dystocia'],
    'mother_outcomes' => ['well', 'referred', 'died'],
    'newborn_outcomes' => ['live_birth', 'stillbirth', 'neonatal_death'],
    'urine_protein' => ['nil', 'trace', 'plus1', 'plus2', 'plus3'],

    // The days within which a newborn's first (birth) doses should be given.
    'birth_dose_days' => 14,
    // A dose past its due age by more than this many days is overdue.
    'overdue_grace_days' => 28,

    // The child's routine doses: key => [name, due age in days after birth, group]. EXAMPLE schedule - see the note above.
    'epi' => [
        'bcg' => ['BCG', 0, 'Birth'],
        'opv0' => ['OPV 0', 0, 'Birth'],
        'penta1' => ['Pentavalent 1', 42, '6 weeks'], 'opv1' => ['OPV 1', 42, '6 weeks'], 'pcv1' => ['PCV 1', 42, '6 weeks'],
        'penta2' => ['Pentavalent 2', 70, '10 weeks'], 'opv2' => ['OPV 2', 70, '10 weeks'], 'pcv2' => ['PCV 2', 70, '10 weeks'],
        'penta3' => ['Pentavalent 3', 98, '14 weeks'], 'opv3' => ['OPV 3', 98, '14 weeks'], 'pcv3' => ['PCV 3', 98, '14 weeks'], 'ipv1' => ['IPV 1', 98, '14 weeks'],
        'mr1' => ['Measles-Rubella 1', 270, '9 months'], 'ipv2' => ['IPV 2', 270, '9 months'],
        'mr2' => ['Measles-Rubella 2', 450, '15 months'],
    ],
];
