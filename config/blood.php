<?php

/*
| Blood Bank defaults (Phase 13). EVERY clinical number here is an EXAMPLE for the blood-bank medical officer to confirm against national
| policy (P-24, OD-13). The hospital's real values are set in Settings (`blood.*`) and override these.
*/
return [
    'abo' => ['O', 'A', 'B', 'AB'],
    'rh' => ['positive', 'negative'],

    // Red-cell compatibility: recipient ABO => donor ABO groups whose red cells are acceptable (Rh handled separately).
    'red_cell_compatible' => ['O' => ['O'], 'A' => ['A', 'O'], 'B' => ['B', 'O'], 'AB' => ['AB', 'A', 'B', 'O']],
    // Plasma-containing components (FFP, cryo): recipient ABO => donor plasma groups acceptable.
    'plasma_compatible' => ['O' => ['O', 'A', 'B', 'AB'], 'A' => ['A', 'AB'], 'B' => ['B', 'AB'], 'AB' => ['AB']],

    // component => label, shelf life (days from collection), storage temperature (°C), storage kind, whether red cells / plasma, ml, charge code
    'components' => [
        'whole_blood' => ['label' => 'Whole blood', 'shelf_days' => 35, 'temp' => [2, 6], 'storage' => 'fridge', 'kind' => 'red', 'volume' => 450, 'charge' => 'BLD-WB'],
        'prbc' => ['label' => 'Packed red cells', 'shelf_days' => 35, 'temp' => [2, 6], 'storage' => 'fridge', 'kind' => 'red', 'volume' => 280, 'charge' => 'BLD-PRBC'],
        'ffp' => ['label' => 'Fresh frozen plasma', 'shelf_days' => 365, 'temp' => [-40, -25], 'storage' => 'freezer', 'kind' => 'plasma', 'volume' => 200, 'charge' => 'BLD-FFP'],
        'platelets' => ['label' => 'Platelet concentrate', 'shelf_days' => 5, 'temp' => [20, 24], 'storage' => 'agitator', 'kind' => 'platelet', 'volume' => 60, 'charge' => 'BLD-PLT'],
        'cryo' => ['label' => 'Cryoprecipitate', 'shelf_days' => 365, 'temp' => [-40, -25], 'storage' => 'freezer', 'kind' => 'plasma', 'volume' => 30, 'charge' => 'BLD-CRYO'],
    ],
    // What a whole-blood donation is separated into (component => units produced)
    'processing' => ['prbc' => 1, 'ffp' => 1, 'platelets' => 1],

    'storage_kinds' => ['fridge', 'freezer', 'agitator', 'quarantine'],

    // Mandatory screening: LabTest code => label. A reactive/positive result blocks the unit. Policy-driven (P-24).
    'screening_tests' => ['BB-HIV' => 'HIV 1/2', 'BB-HBSAG' => 'Hepatitis B (HBsAg)', 'BB-HCV' => 'Hepatitis C', 'BB-VDRL' => 'Syphilis', 'BB-MP' => 'Malaria'],
    'grouping_tests' => ['abo' => 'BB-ABO', 'rh' => 'BB-RH'],

    // Donor eligibility rules (editable). interval days between whole-blood donations by sex.
    'eligibility' => ['min_age' => 18, 'max_age' => 60, 'min_weight_kg' => 50, 'min_hb' => ['M' => 13.0, 'F' => 12.5], 'min_interval_days' => ['M' => 90, 'F' => 120],
        'pulse' => [50, 100], 'systolic' => [100, 180], 'diastolic' => [60, 100], 'max_temp_c' => 37.5],
    'deferral_reasons' => ['low_hemoglobin' => 30, 'recent_illness' => 14, 'recent_donation' => null, 'underweight' => 30, 'medication' => 30, 'tattoo_piercing' => 180, 'travel_malaria_area' => 180, 'high_risk_behaviour' => 365, 'positive_screen' => 'permanent', 'other' => 30],

    'collection_volume_ml' => 450,
    'adverse_event_types' => ['vasovagal', 'hematoma', 'nausea', 'citrate_reaction', 'arterial_puncture', 'other'],
    'post_donation_observation_minutes' => 15,

    // Requests and compatibility
    'priorities' => ['routine', 'urgent', 'emergency', 'massive'],
    'sample_validity_hours' => 72,
    'second_sample_required_when_no_group_on_file' => true,
    'reservation_hours' => ['routine' => 24, 'urgent' => 6, 'emergency' => 2, 'massive' => 2],
    'rejection_reasons' => ['unlabelled', 'mislabelled', 'haemolysed', 'insufficient', 'wrong_patient', 'group_discordant', 'expired_sample', 'other'],

    // Issue and return
    'return' => ['max_minutes_out' => 30, 'temp' => [1, 10]],
    'transfusion' => ['max_hours' => 4, 'observation_minutes' => [0, 15, 60]],
    'reaction_severities' => ['mild', 'moderate', 'severe'],
    'reaction_types' => ['febrile_non_haemolytic', 'allergic', 'acute_haemolytic', 'anaphylaxis', 'taco', 'trali', 'septic', 'other'],

    'discard_reasons' => ['expired', 'positive_screen', 'temperature_excursion', 'damaged', 'returned_unfit', 'discordant_group', 'recalled', 'other'],
    'expiry_alert_days' => 3,
    'low_stock' => ['prbc' => 10, 'ffp' => 6, 'platelets' => 4, 'whole_blood' => 5, 'cryo' => 2],

    // Massive transfusion protocol: pack => component => units
    'mtp_pack' => ['prbc' => 4, 'ffp' => 4, 'platelets' => 1],
    'emergency_release_group' => ['abo' => 'O', 'rh' => 'negative'],

    'alert_roles' => ['blood_bank_technician', 'blood_bank_medical_officer', 'doctor'],
];
