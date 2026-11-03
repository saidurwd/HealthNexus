<?php

/*
| Facility and support services (Phase 22). Every value is a starting point for the hospital: Settings `facility.<key>` win over this file.
*/
return [
    'timezone' => 'Asia/Dhaka',

    'asset' => [
        'categories' => ['biomedical', 'it', 'furniture', 'vehicle', 'plant', 'other'],
        'risk_classes' => ['low', 'medium', 'high'],
        // High-risk equipment may not be used while a calibration is overdue or has failed.
        'block_on_overdue_calibration' => true,
        'warranty_warning_days' => 60,
    ],

    'maintenance' => [
        'priorities' => ['low', 'normal', 'urgent'],
        // The daily check opens a work order this many days before a plan falls due.
        'lead_days' => 7,
        // Breakdown and calibration work on high-risk equipment is checked by someone other than the engineer.
        'second_person_verifies' => true,
    ],

    'housekeeping' => [
        'turnover_target_minutes' => 45,
        // A clean after isolation or an infection is inspected by a second person before the bed is released.
        'inspect_kinds' => ['isolation_terminal'],
        'checklists' => [
            'bed_turnover' => ['Mattress and bed frame wiped with disinfectant', 'Bed linen changed', 'Locker and table wiped', 'Floor mopped', 'Waste bins emptied', 'Call bell and oxygen outlet checked'],
            'isolation_terminal' => ['All linen bagged and removed', 'All surfaces and equipment disinfected', 'Walls, curtains and fixtures cleaned', 'Floor mopped with disinfectant', 'Waste handled as infectious', 'Room left to air before use'],
            'room' => ['Floors cleaned', 'Surfaces wiped', 'Bins emptied', 'Washroom cleaned'],
            'theatre' => ['Table and lights wiped', 'Floor cleaned with disinfectant', 'Waste removed', 'Equipment wiped down'],
        ],
        'audit_pass_percent' => 85,
    ],

    'cssd' => [
        'methods' => ['steam', 'eto', 'plasma'],
        'shelf_life_days' => ['steam' => 30, 'eto' => 180, 'plasma' => 90],
        'second_person_releases' => true,
    ],

    'linen' => [
        'movements' => ['issue', 'collect_soiled', 'send_laundry', 'receive_clean', 'receive_new', 'condemn'],
        'default_par' => 60,
    ],

    'waste' => [
        'categories' => ['infectious', 'sharps', 'pathological', 'pharmaceutical', 'chemical', 'general'],
        // Difference allowed between a manifest and the records in it (kg).
        'tolerance_kg' => 0.0,
    ],

    'diet' => [
        'meals' => ['breakfast', 'lunch', 'snack', 'dinner'],
        'textures' => ['normal', 'soft', 'minced', 'liquid', 'nil_by_mouth'],
    ],

    'ambulance' => [
        'types' => ['basic', 'advanced', 'transport', 'mortuary'],
        'trip_kinds' => ['emergency', 'transfer', 'discharge', 'referral', 'mortuary'],
    ],

    'mortuary' => [
        'second_person_releases' => true,
        'id_types' => ['national_id', 'birth_certificate', 'passport', 'driving_licence', 'other'],
    ],

    'visitor' => [
        // Visiting hours (local time); outside them only attendants may enter.
        'hours' => [['10:00', '12:00'], ['16:00', '19:00']],
        'max_attendants' => 1,
        'max_visitors_at_once' => 2,
        'id_types' => ['national_id', 'passport', 'driving_licence', 'other'],
    ],

    'dashboard' => ['default_days' => 30],
];
