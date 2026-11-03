<?php

return [
    'backup' => [
        'disk' => env('BACKUP_DISK', 'local'),
        'directory' => 'backups',
        'keep_days' => 14,
        // Dumps are encrypted with AES-256 when set. Required in production (pilot:readiness fails without it).
        'passphrase' => env('BACKUP_PASSPHRASE'),
        // Tables whose exact row counts are recorded at backup time and compared after a restore drill.
        'check_tables' => ['users', 'organizations', 'patients', 'encounters', 'billing_invoices', 'billing_payments', 'stock_ledger', 'audit_logs'],
        'max_age_hours' => 26,
    ],

    'restore_drill' => ['max_age_days' => 35],

    'monitoring' => [
        'failed_jobs_max' => 0,
        'queue_depth_max' => 500,
        'disk_free_min_percent' => 15,
        'scheduler_heartbeat_max_minutes' => 3,
        'unacknowledged_critical_alert_minutes' => 15,
    ],

    'downtime' => ['census_hours' => 6, 'keep_days' => 7],
];
