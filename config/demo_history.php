<?php

use App\Services\Demo\History\Modules\AppointmentsAndOpdHistory;
use App\Services\Demo\History\Modules\BillingCatalogHistory;
use App\Services\Demo\History\Modules\BillingHistory;
use App\Services\Demo\History\Modules\CatalogHistory;
use App\Services\Demo\History\Modules\EdHistory;
use App\Services\Demo\History\Modules\IcuHistory;
use App\Services\Demo\History\Modules\IpdHistory;
use App\Services\Demo\History\Modules\LabHistory;
use App\Services\Demo\History\Modules\NursingHistory;
use App\Services\Demo\History\Modules\OtHistory;
use App\Services\Demo\History\Modules\PatientsHistory;
use App\Services\Demo\History\Modules\PharmacyHistory;
use App\Services\Demo\History\Modules\RadiologyHistory;

/*
| Demo history (AI-RULES 16). Order matters: later classes build on what earlier ones left in the HistoryContext.
| Every phase adds its class here. `php artisan demo:history` runs them; `demo:build` calls it by default.
*/
return [
    'modules' => [
        PatientsHistory::class,
        CatalogHistory::class,
        BillingCatalogHistory::class,
        AppointmentsAndOpdHistory::class,
        EdHistory::class,
        LabHistory::class,
        RadiologyHistory::class,
        PharmacyHistory::class,
        IpdHistory::class,
        NursingHistory::class,
        OtHistory::class,
        IcuHistory::class,
        BillingHistory::class,
    ],

    'months' => 6,
    'visits_per_day' => 8,
];
