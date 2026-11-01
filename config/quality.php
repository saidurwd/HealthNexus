<?php

/*
| Quality, safety and compliance (Phase 23). Every value is a starting point for the hospital's quality committee: Settings `quality.<key>` win over this file.
| Nothing here assigns blame: categories describe systems and processes, never persons.
*/
return [
    'timezone' => 'Asia/Dhaka',

    'incident' => [
        'kinds' => ['incident', 'near_miss', 'adverse_event', 'sentinel'],
        'categories' => ['fall', 'medication', 'pressure_injury', 'procedure', 'blood', 'infection', 'equipment', 'identification', 'communication', 'diet', 'behaviour', 'security', 'fire_safety', 'documentation', 'other'],
        'harm' => ['none', 'minor', 'moderate', 'major', 'catastrophic'],
        // Days allowed to close an incident, by the harm level after review (or as reported).
        'due_days' => ['none' => 30, 'minor' => 30, 'moderate' => 21, 'major' => 14, 'catastrophic' => 7],
        // Harm levels that need an investigation with a second person's sign-off.
        'investigate_from' => 'major',
        // Contributing-factor categories for the root causes: systems, never persons.
        'cause_categories' => ['communication', 'process', 'equipment', 'staffing', 'environment', 'training', 'patient_factors', 'documentation', 'other'],
        // Categories for which a patient is expected.
        'patient_categories' => ['fall', 'medication', 'pressure_injury', 'procedure', 'blood', 'infection', 'identification', 'diet'],
        // Whether a report may be made anonymously.
        'allow_anonymous' => true,
    ],

    'action' => [
        // Corrective and preventive actions are checked by a second person; the owner cannot verify their own.
        'second_person_verifies' => true,
        'default_days' => 30,
        // The daily check alerts the quality managers when an action is this many days past due (0 = the day after).
        'overdue_alert_after_days' => 0,
    ],

    'infection' => [
        'hai_types' => ['clabsi', 'cauti', 'ssi', 'vap', 'cdiff', 'mrsa', 'other'],
        'precautions' => ['contact', 'droplet', 'airborne', 'protective', 'enteric'],
        // An infection starting on or after this hospital day is counted as hospital-acquired.
        'hospital_acquired_from_day' => 3,
        // An outbreak is opened when this many confirmed or suspected cases share a ward and a type (or organism) within the window.
        'outbreak_cases' => 3,
        'outbreak_window_days' => 14,
        'hand_hygiene_target' => 80,
    ],

    'indicator' => [
        // A death must be reviewed within this many days.
        'mortality_review_days' => 30,
        'readmission_days' => 30,
        'months_back' => 12,
    ],

    'audit' => [
        // A criterion met less often than this (percent) is a gap that needs an action.
        'gap_threshold' => 90,
    ],

    'mortality' => [
        'classifications' => ['expected', 'unexpected_unavoidable', 'potentially_preventable', 'preventable'],
    ],

    'document' => [
        'categories' => ['policy', 'sop', 'protocol', 'guideline', 'form'],
        // The author of a version cannot approve it.
        'second_person_approves' => true,
        'default_review_months' => 12,
        // The daily check alerts the owner this many days before a review is due.
        'review_warning_days' => 30,
    ],

    'privilege' => [
        // A privilege needs a second person's decision; the requester cannot grant their own.
        'second_person_decides' => true,
        'default_validity_months' => 24,
        'expiry_warning_days' => 60,
    ],

    'compliance' => [
        'kinds' => ['licence', 'inspection', 'certificate', 'insurance', 'other'],
        'warning_days' => 60,
    ],

    'committee' => [
        'kinds' => ['safety', 'infection', 'mm', 'other'],
    ],

    'dashboard' => [
        'default_months' => 6,
    ],
];
