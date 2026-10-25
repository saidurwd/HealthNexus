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
];
