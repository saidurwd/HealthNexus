<?php

/*
| Report registry (guideline 14.4). Add a ReportDefinition class here and it can be exported through ReportExportService.
| Formats: csv now; xlsx and pdf need packages that have not been approved yet (packages policy, guideline 5) and
| the Bangla PDF template work of H-08.
*/
return [
    'definitions' => [
        \App\Reports\PatientListReport::class,
    ],

    'formats' => ['csv'],

    'disk' => env('REPORT_EXPORT_DISK', 'local'),

    'expiry_hours' => 24,
];
