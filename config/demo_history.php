<?php

use App\Services\Demo\History\Modules\AppointmentsAndOpdHistory;
use App\Services\Demo\History\Modules\BillingCatalogHistory;
use App\Services\Demo\History\Modules\BillingHistory;
use App\Services\Demo\History\Modules\BloodDonationsHistory;
use App\Services\Demo\History\Modules\BloodTransfusionHistory;
use App\Services\Demo\History\Modules\CatalogHistory;
use App\Services\Demo\History\Modules\EdHistory;
use App\Services\Demo\History\Modules\HrHistory;
use App\Services\Demo\History\Modules\IcuHistory;
use App\Services\Demo\History\Modules\InsuranceClaimsHistory;
use App\Services\Demo\History\Modules\InsuranceSetupHistory;
use App\Services\Demo\History\Modules\InteropHistory;
use App\Services\Demo\History\Modules\InventoryHistory;
use App\Services\Demo\History\Modules\IpdHistory;
use App\Services\Demo\History\Modules\LabHistory;
use App\Services\Demo\History\Modules\NursingHistory;
use App\Services\Demo\History\Modules\OtHistory;
use App\Services\Demo\History\Modules\PatientsHistory;
use App\Services\Demo\History\Modules\PharmacyHistory;
use App\Services\Demo\History\Modules\PortalHistory;
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
        InsuranceSetupHistory::class,
        AppointmentsAndOpdHistory::class,
        EdHistory::class,
        BloodDonationsHistory::class,
        LabHistory::class,
        RadiologyHistory::class,
        PharmacyHistory::class,
        IpdHistory::class,
        NursingHistory::class,
        OtHistory::class,
        IcuHistory::class,
        BloodTransfusionHistory::class,
        InventoryHistory::class,
        HrHistory::class,
        BillingHistory::class,
        InsuranceClaimsHistory::class,
        PortalHistory::class,
        InteropHistory::class,
    ],

    'months' => 6,
    'visits_per_day' => 8,
];
