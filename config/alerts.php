<?php

/*
| Alert Service defaults (guideline 10.5). The ladders below are EXAMPLES; each hospital's clinical governance sets the real
| ones in the setting `alerts.escalation_policies` (same shape), which overrides these per policy key.
| A ladder level: after_minutes since the alert was raised, and the roles to notify. Level 0 = the recipients the
| raising module names (e.g. the ordering clinician) and is not listed here.
*/
return [
    'severities' => ['info', 'warning', 'critical'],

    // An open alert with the same dedup key inside this window is returned instead of raising another (alert-fatigue control).
    'dedup_window_minutes' => 15,

    'channels' => ['database'],

    // Categories whose acknowledgement needs the read-back checkbox (closed-loop critical results, guideline 10.6).
    'read_back_required_categories' => ['critical_lab', 'critical_radiology'],

    'policies' => [
        'critical' => [
            ['after_minutes' => 10, 'roles' => ['charge_nurse']],
            ['after_minutes' => 20, 'roles' => ['nursing_supervisor']],
            ['after_minutes' => 30, 'roles' => ['hospital_admin']],
        ],
        'warning' => [
            ['after_minutes' => 30, 'roles' => ['charge_nurse']],
        ],
    ],
];
