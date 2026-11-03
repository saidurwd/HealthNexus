<?php

/*
| BI and analytics (Phase 19). Every value is a starting point: Settings `analytics.<key>` win over this file.
*/
return [
    // The warehouse connection name (config/database.php). Empty = the application's own connection (tables prefixed dim_/fact_).
    // With a reporting replica, point this at it: dashboards then never read the operational tables.
    'connection' => env('ANALYTICS_DB_CONNECTION'),

    // The key the patient pseudonym is made with. It must not be stored in the warehouse; empty = the application key.
    'pseudonym_key' => env('ANALYTICS_PSEUDONYM_KEY'),

    // Dates in the warehouse are the hospital's local dates; times in the operational tables are UTC.
    'timezone' => 'Asia/Dhaka',

    'load' => [
        'chunk' => 500,
        // A source not loaded for this many minutes is reported as stale.
        'stale_minutes' => 180,
        // How far back a re-read of changed rows reaches (hours), so rows changed while a load ran are not missed.
        'overlap_hours' => 2,
        // First load looks back this many months (0 = everything).
        'first_load_months' => 0,
        'calendar_years_ahead' => 1,
        // The daily-counts table is rebuilt for this many days back each run.
        'daily_metrics_days' => 400,
    ],

    // Counts below this are hidden from people without analytics.sensitive.view (small numbers can identify a person).
    'small_cell' => 5,

    'research' => [
        'k_anonymity' => 5,
        'max_rows' => 100000,
        'dates' => 'month', // month | year
    ],

    'benchmark' => ['min_peers' => 3],

    'forecast' => ['horizon_days' => 14, 'history_days' => 120, 'backtest_days' => 14],

    // National reporting pack (HMIS-style aggregates, DHIS2 dataValueSet). Codes stay empty until the national onboarding (verify the requirements, P-18).
    'national' => [
        'data_set' => env('ANALYTICS_DHIS2_DATASET'),
        'org_unit' => env('ANALYTICS_DHIS2_ORGUNIT'),
        'elements' => [], // indicator key => DHIS2 data element id
    ],

    'dashboard' => ['refresh_minutes' => 15, 'default_days' => 30],

    'schedules' => ['max_recipients' => 10, 'allowed_formats' => ['csv', 'pdf']],
];
