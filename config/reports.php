<?php

use App\Reports\EdVisitsReport;
use App\Reports\PatientListReport;

/*
| Report registry (guideline 14.4). Add a ReportDefinition class here and it can be exported through ReportExportService.
| Formats: csv and xlsx. (PDF of a list report is not offered: documents print through the Document Service.)
*/
return [
    'definitions' => [
        PatientListReport::class,
        EdVisitsReport::class,
    ],

    'formats' => ['csv', 'xlsx'],

    'disk' => env('REPORT_EXPORT_DISK', 'local'),

    'expiry_hours' => 24,
];
