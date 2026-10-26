<?php

use App\Services\Demo\History\Modules\AppointmentsAndOpdHistory;
use App\Services\Demo\History\Modules\PatientsHistory;

/*
| Demo history (AI-RULES 16). Order matters: later classes build on what earlier ones left in the HistoryContext.
| Every phase adds its class here. `php artisan demo:history` runs them; `demo:build` calls it by default.
*/
return [
    'modules' => [
        PatientsHistory::class,
        AppointmentsAndOpdHistory::class,
    ],

    'months' => 6,
    'visits_per_day' => 8,
];
