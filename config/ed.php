<?php

/*
| Emergency Department defaults (Phase 12). Every number here is an EXAMPLE to be confirmed by the hospital's clinical champions (OD-12);
| the hospital's real values live in settings (ed.triage_scale, ed.door_to_triage_minutes …) and override these.
*/
return [
    'zone_types' => ['triage', 'resuscitation', 'acute', 'minor', 'observation', 'isolation', 'paediatric', 'waiting'],

    'arrival_modes' => ['walk_in', 'ambulance', 'police', 'referral', 'transfer'],

    // A local 5-level scale (not a licensed ESI/MTS/CTAS reproduction). Level 1 = most urgent.
    // door_to_doctor: target minutes from arrival to first doctor contact. reassess: re-triage interval while waiting.
    'triage_scale' => [
        1 => ['name' => 'Immediate', 'color' => 'danger', 'door_to_doctor' => 0, 'reassess' => 5],
        2 => ['name' => 'Emergent', 'color' => 'orange', 'door_to_doctor' => 10, 'reassess' => 15],
        3 => ['name' => 'Urgent', 'color' => 'warning', 'door_to_doctor' => 30, 'reassess' => 30],
        4 => ['name' => 'Less urgent', 'color' => 'success', 'door_to_doctor' => 60, 'reassess' => 60],
        5 => ['name' => 'Non-urgent', 'color' => 'info', 'door_to_doctor' => 120, 'reassess' => 120],
    ],

    'door_to_triage_minutes' => 10,

    'closed_statuses' => ['dispositioned', 'closed', 'cancelled'],

    'alert_roles' => ['doctor', 'charge_nurse'],

    // Resuscitation timeline event types (the clock starts at the first event of the record).
    'resus_events' => ['arrival_resus', 'cpr_started', 'airway_secured', 'iv_access', 'defibrillation', 'adrenaline_given', 'rosc', 'intubation', 'code_blue_called', 'team_arrived', 'note', 'stopped'],

    // Deterioration limits for monitoring (EXAMPLE values for clinical approval, OD-12). Outside a range raises an alert.
    // key => [low, high] (null = no limit on that side).
    'deterioration' => [
        'heart_rate' => [40, 130], 'respiratory_rate' => [8, 30], 'oxygen_saturation' => [90, null],
        'body_temperature' => [35.0, 39.5], 'systolic_bp' => [90, 220],
    ],

    // Consultation answer targets in minutes by urgency (EXAMPLE values, OD-12).
    'consult_sla' => ['emergent' => 15, 'urgent' => 30, 'routine' => 120],

    // Observation unit billing rule (EXAMPLE values, OD-12): billed hours = max(minimum_hours, ceil((minutes - grace_minutes) / 60)).
    // An observation stay longer than long_stay_hours raises a review alert.
    'observation' => ['service_code' => 'ED-OBS-HR', 'grace_minutes' => 10, 'minimum_hours' => 1, 'long_stay_hours' => 24],

    // How an emergency visit can end. `needs`: the details the decision must carry (all are checked by EdDispositionService).
    'dispositions' => [
        'discharge' => ['needs' => []],
        'admit_ipd' => ['needs' => ['reason']],
        'icu' => ['needs' => ['reason']],
        'surgery' => ['needs' => ['reason']],
        'transfer' => ['needs' => ['destination', 'reason']],
        'referral' => ['needs' => ['destination', 'reason']],
        'lwbs' => ['needs' => []],
        'lama' => ['needs' => ['reason', 'risks_explained']],
        'death' => ['needs' => ['time_of_death', 'cause']],
    ],
];
